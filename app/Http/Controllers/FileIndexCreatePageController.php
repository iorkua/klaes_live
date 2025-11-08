<?php

namespace App\Http\Controllers;

use App\Models\ApplicationMother;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileIndexCreatePageController extends Controller
{
    public function __invoke()
    {
        try {
            $PageTitle = 'Create File Index';
            $PageDescription = 'Create a new file index record';

            $availableApplications = $this->getAvailableApplications();

            return view('fileindexing.addons.create_indexing', compact('PageTitle', 'PageDescription', 'availableApplications'));
        } catch (Exception $e) {
            Log::error('Error loading file indexing standalone create page', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'Error loading create form: ' . $e->getMessage());
        }
    }

    private function getAvailableApplications()
    {
        return ApplicationMother::on('sqlsrv')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('file_indexings')
                    ->whereRaw('file_indexings.main_application_id = mother_applications.id');
            })
            ->select('id', 'fileno', 'np_fileno', 'first_name', 'middle_name', 'surname', 'corporate_name', 'applicant_type')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }
}
