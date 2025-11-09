<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Lab404\Impersonate\Models\Impersonate;


class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use Impersonate;

    // Specify SQL Server connection
    protected $connection = 'sqlsrv';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'type',
        'phone_number',
        'profile',
        'lang',
        'subscription',
        'subscription_expire_date',
        'parent_id',
        'is_active',
        'assign_role', // stores user_role ids as comma-separated
        'department_id', // correct field for department
        'user_level',    // new field for user level
        'user_type',     // new field for user type
        'username', // new field for username
    ];

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['name'];

    public function canImpersonate()
    {
        // Example: Only admins can impersonate others
        return $this->type == 'super admin';
    }

    public function totalUser()
    {
        return User::where('parent_id', $this->id)->count();
    }

    public function getNameAttribute()
    {
        return ucfirst($this->first_name) . ' ' . ucfirst($this->last_name);
    }


    public function totalContact()
    {
        return Contact::where('parent_id', '=', parentId())->count();
    }

    public function roleWiseUserCount($role)
    {
        return User::where('type', $role)->where('parent_id', parentId())->count();
    }
    
    public static function getDevice($user)
    {
        $mobileType = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
        $tabletType = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';
        if (preg_match_all($mobileType, $user)) {
            return 'mobile';
        } else {
            if (preg_match_all($tabletType, $user)) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }
    }

    public function totalDocument()
    {
        return Document::where('parent_id', '=', parentId())->count();
    }

    // Modified to handle null subscription
    public function subscriptions()
    {
        return $this->hasOne('App\Models\Subscription', 'id', 'subscription');
    }

    // Modified to handle null subscription
    public function SubscriptionLeftDay()
    {
        // No longer needed for this application
        return '<span class="text-success">' . __('Active') . '</span>';
    }

    /**
     * Get the department that the user belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user roles assigned to this user
     */
    public function userRoles()
    {
        if(empty($this->assign_role)) {
            return collect([]);
        }
        
        $roleIds = explode(',', $this->assign_role);
        return UserRole::whereIn('id', $roleIds)->get();
    }

    /**
     * Get session locks for this user
     */
    public function sessionLocks()
    {
        return $this->hasMany(UserSessionLock::class);
    }
    
    /**
     * Check if user has permission
     * 
     * @param string|array $abilities
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($abilities, $arguments = [])
    {
        // For compatibility with previous code that used Spatie permissions
        // Now we'll check if the user has appropriate roles
        if ($this->type == 'super admin') {
            return true;
        }
        
        // Basic permission checks - customize as needed
        $adminPermissions = ['manage user', 'create user', 'edit user', 'delete user', 'show user', 'manage logged history', 'delete logged history'];
        
        if (in_array($abilities, $adminPermissions) && in_array($this->type, ['owner', 'admin', 'super admin'])) {
            return true;
        }
        
        return false;
    }

    public static $systemModules = [
        'user',
        'document',
        'reminder',
        'comment',
        'version',
        'mail',
        'category',
        'tag',
        'contact',
        'note',
        'logged history',
        'pricing transation',
        'account settings',
        'password settings',
        'general settings',
        'company settings',
    ];
}

