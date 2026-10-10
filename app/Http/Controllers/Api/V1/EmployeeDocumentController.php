<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(),'documents.view');
        $query = EmployeeDocument::query()->select(['id','employee_id','title','document_type','created_at','updated_at']);
        if (! Access::isHr($request->user())) $query->where('employee_id',Access::ownEmployee($request->user())->id);
        return $query->orderByDesc('id')->paginate(20);
    }

    public function show(Request $request, EmployeeDocument $employeeDocument)
    {
        $this->checkView($request,$employeeDocument);
        return $employeeDocument->only(['id','employee_id','title','document_type','created_at']);
    }

    public function store(Request $request)
    {
        Access::require($request->user(),'documents.manage');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],
            'title'=>['required','string','max:200'],
            'document_type'=>['required','string','max:100'],
            'file'=>['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
        ]);
        $path = $request->file('file')->store('employee-documents/'.$data['employee_id'],'local');
        $document = EmployeeDocument::create([
            'employee_id'=>$data['employee_id'],'title'=>$data['title'],
            'document_type'=>$data['document_type'],'file_path'=>$path,
            'uploaded_by'=>$request->user()->id,
        ]);
        return response()->json($document->only(['id','employee_id','title','document_type']),201);
    }

    public function download(Request $request, EmployeeDocument $employeeDocument)
    {
        $this->checkView($request,$employeeDocument);
        abort_unless(Storage::disk('local')->exists($employeeDocument->file_path),404);
        return Storage::disk('local')->download($employeeDocument->file_path);
    }

    public function destroy(Request $request, EmployeeDocument $employeeDocument)
    {
        Access::require($request->user(),'documents.manage');
        // Keep employment documents to comply with records retention; do not hard-delete automatically.
        abort(405,'Document deletion requires a separate retention policy.');
    }

    private function checkView(Request $request, EmployeeDocument $employeeDocument): void
    {
        Access::require($request->user(),'documents.view');
        abort_unless(Access::isHr($request->user()) || $employeeDocument->employee?->user_id === $request->user()->id,403);
    }
}
