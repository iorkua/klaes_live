<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LoggedHistory;
use App\Models\Notification;
use App\Models\PackageTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserType;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage; // added for profile image storage

class UserController extends Controller
{

    public function index()
    {
        $PageTitle= __('User');
        $PageDescription = __('User List');
        if (\Auth::user()->can('manage user')) {
            $users = User::all();
            return view('user.index', compact('users', 'PageTitle', 'PageDescription'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function create()
    {
        try {
            // Get departments using the default connection
            $departments = Department::where('is_active', 1)->pluck('name', 'id');
            
            // Get user types using sqlsrv connection
            $userTypes = UserType::on('sqlsrv')
                                ->active()
                                ->orderByPriority()
                                ->get(['id', 'name', 'code', 'description']);
            
            // Get user roles using sqlsrv connection
            $userRoles = UserRole::on('sqlsrv')
                               ->where('is_active', 1)
                               ->get(['id', 'name', 'department_id', 'level', 'user_type']);
            
            return view('user.create', compact('departments', 'userTypes', 'userRoles'));
        } catch (\Exception $e) {
            \Log::error('Error in UserController@create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error loading user creation form: ' . $e->getMessage());
        }
    }

    /**
     * Get user levels for a specific user type
     */
    public function getUserLevels($userTypeId)
    {
        try {
            $userLevels = UserLevel::on('sqlsrv')
                                  ->forUserType($userTypeId)
                                  ->active()
                                  ->orderByPriority()
                                  ->get(['id', 'name', 'code', 'description']);
            
            \Log::info('User levels fetched', [
                'user_type_id' => $userTypeId,
                'levels_count' => $userLevels->count(),
                'levels' => $userLevels->toArray()
            ]);
            
            return response()->json($userLevels);
        } catch (\Exception $e) {
            \Log::error('Error fetching user levels', [
                'user_type_id' => $userTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load user levels: ' . $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create user')) {
            if (\Auth::user()->type == 'super admin') {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required',
                        'username' => 'required|unique:users', // add username validation
                        'password' => 'required|min:6',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $user = new User();
                $user->name = $request->name;
                $user->username = $request->username; // save username
                $user->email = $request->filled('email') ? $request->email : uniqid('temp_') . '@klaes.com.ng';
                $user->assign_role = isset($request->user_role) ? implode(',', $request->user_role) : null;
                $user->password = \Hash::make($request->password);
                // allow either phone or phone_number
                $user->phone_number = $request->input('phone_number', $request->input('phone'));
                $user->department_id = $request->department_id; // Save department_id
                $user->user_level = $request->user_level; // Save user_level
                $user->type = 'owner';
                $user->lang = 'english';
                $user->subscription = 1;
                $user->parent_id = parentId();
                $user->email_verified_at = now();
                
                // Handle optional profile image upload
                if ($request->hasFile('profile')) {
                    $file = $request->file('profile');
                    $filename = uniqid('profile_') . '_' . time() . '.' . $file->getClientOriginalExtension();
                    // store in public disk under upload/profile
                    Storage::disk('public')->putFileAs('upload/profile', $file, $filename);
                    $user->profile = $filename;
                } else {
                    $user->profile = 'avatar.png';
                }

                $user->save();
                defaultTemplate($user->id);

                $module = 'user_create';
                $notification = Notification::where('parent_id', parentId())->where('module', $module)->first();
                $setting = settings();
                $errorMessage = '';
                if (!empty($notification) && $notification->enabled_email == 1) {
                    $notification_responce = MessageReplace($notification, $user->id);
                    $data['subject'] = $notification_responce['subject'];
                    $data['message'] = $notification_responce['message'];
                    $data['module'] = $module;
                    $data['password'] = $request->password;
                    $data['logo'] = $setting['company_logo'];
                    $to = $user->email;

                    $response = commonEmailSend($to, $data);
                    if ($response['status'] == 'error') {
                        $errorMessage = '<br><span class="text-danger">' . $response['message'] . '</span>';
                    }
                }
                return redirect()->route('users.index')->with('success', __('User successfully created.') . $errorMessage);
            } else {

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required',
                        'last_name' => 'required',
                        'password' => 'required|min:6',
                        'department_id' => 'required',
                        'user_type' => 'required|string|in:Management,Operations,ALL,User,System',
                        'user_level' => 'required|string|in:Administrative,Technical,Finance,Lowest,High,Highest',
                        'user_role' => 'required|array',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $pricing_feature_settings = getSettingsValByIdName(1, 'pricing_feature');
                if ($pricing_feature_settings == 'on') {
                    $ids = parentId();
                    $authUser = \App\Models\User::find($ids);
                    $totalUser = $authUser->totalUser();
                    $subscription = Subscription::find($authUser->subscription);
                    if ($totalUser >= $subscription->user_limit && $subscription->user_limit != 0) {
                        return redirect()->back()->with('error', __('Your user limit is over, please upgrade your subscription.'));
                    }
                }
                
                $user = new User();
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->username = $request->username; // save username
                $user->email = $request->filled('email') ? $request->email : uniqid('temp') . '@klaes.com.ng';
                // allow either phone or phone_number
                $user->phone_number = $request->input('phone_number', $request->input('phone'));
                $user->password = \Hash::make($request->password);
                $user->department_id = $request->department_id;
                $user->user_level = $request->user_level;
                $user->type = $request->user_type; // Use the selected user type
                $user->email_verified_at = now();
                
                // Handle required profile image upload
                if ($request->hasFile('profile')) {
                    $file = $request->file('profile');
                    $filename = uniqid('profile_') . '_' . time() . '.' . $file->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('upload/profile', $file, $filename);
                    $user->profile = $filename;
                } else {
                    // Fallback, though validation requires it
                    $user->profile = 'avatar.png';
                }
                
                $user->lang = 'english';
                $user->parent_id = parentId();
                $user->assign_role = isset($request->user_role) ? implode(',', $request->user_role) : null;
                $user->save();
                
                $module = 'user_create';
                $notification = Notification::where('parent_id', parentId())->where('module', $module)->first();
                if (!empty($notification)) {
                    $notification->password=$request->password;
                }
                $setting = settings();
                $errorMessage = '';
                if (!empty($notification) && $notification->enabled_email == 1) {
                    $notification_responce = MessageReplace($notification, $user->id);
                    $data['subject'] = $notification_responce['subject'];
                    $data['message'] = $notification_responce['message'];
                    $data['module'] = $module;
                    $data['password'] = $request->password;
                    $data['logo'] = $setting['company_logo'];
                    $to = $user->email;

                    $response = commonEmailSend($to, $data);
                    if ($response['status'] == 'error') {
                        $errorMessage=$response['message'];
                    }
                }

                return redirect()->route('users.index')->with('success', __('User successfully created.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function show(User $user)
    {
        if (!\Auth::user()->can('show user')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        } else {
            $settings = settings();
            $transactions = PackageTransaction::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
            // Remove subscriptions variable
            return view('user.show', compact('user', 'transactions', 'settings'));
        }
    }


    public function edit($id)
    {
        $user = User::findOrFail($id);
        $departments = Department::where('is_active', 1)->pluck('name', 'id');
        
        // Get user roles with user_type and level columns (same as create method)
        $userRoles = UserRole::on('sqlsrv')
                           ->where('is_active', 1)
                           ->get(['id', 'name', 'department_id', 'level', 'user_type']);
        
        // Get user's assigned roles as an array
        $userAssignedRoles = !empty($user->assign_role) ? explode(',', $user->assign_role) : [];
        
        return view('user.edit', compact('user', 'departments', 'userRoles', 'userAssignedRoles'));
    }


    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit user')) {
            if (\Auth::user()->type == 'super admin') {
                $user = User::findOrFail($id);

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required',
                        'username' => 'required|unique:users,username,' . $id, // add username validation
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $userData = $request->all();
                $user->fill($userData);
                $user->username = $request->username; // update username
                $user->email = $request->filled('email') ? $request->email : uniqid('temp') . '@klaes.com.ng';
                $user->save();

                return redirect()->route('users.index')->with('success', 'User successfully updated.');
            } else {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required',
                        'last_name' => 'required',
                        'department_id' => 'required',
                        'user_level' => 'required|string|in:Administrative,Technical,Finance,Lowest,Highest,High',
                        'user_role' => 'required|array',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                // Get the first selected role to set as the user type
                $firstRoleName = $request->user_role[0];
                $userRole = UserRole::where('name', $firstRoleName)->first();
                
                $user = User::findOrFail($id);
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->username = $request->username; // update username
                $user->email = $request->filled('email') ? $request->email : uniqid('temp') . '@klaes.com.ng';
                $user->phone_number = $request->phone_number;
                $user->department_id = $request->department_id;
                $user->user_level = $request->user_level;
                $user->type = $userRole ? $userRole->name : null;
                $user->assign_role = isset($request->user_role) ? implode(',', $request->user_role) : null;
                $user->save();
                
                return redirect()->route('users.index')->with('success', 'User successfully updated.');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function destroy($id)
    {

        if (\Auth::user()->can('delete user')) {
            $user = User::find($id);
            $user->delete();

            return redirect()->route('users.index')->with('success', __('User successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function loggedHistory()
    {
        $ids = parentId();
        $authUser = \App\Models\User::find($ids);
        $subscription = \App\Models\Subscription::find($authUser->subscription);

        if (\Auth::user()->can('manage logged history') && $subscription->enabled_logged_history == 1) {
            $histories = LoggedHistory::where('parent_id', parentId())->get();
            return view('logged_history.index', compact('histories'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function loggedHistoryShow($id)
    {
        if (\Auth::user()->can('manage logged history')) {
            $histories = LoggedHistory::find($id);
            return view('logged_history.show', compact('histories'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function loggedHistoryDestroy($id)
    {
        if (\Auth::user()->can('delete logged history')) {
            $histories = LoggedHistory::find($id);
            $histories->delete();
            return redirect()->back()->with('success', 'Logged history succefully deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
