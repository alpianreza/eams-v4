<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('master-data.employees.index', ['employees' => Employee::latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Employee::create($data);

        return back()->with('status', 'Karyawan ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validateData($request, $employee->id);

        $employee->update($data);

        return back()->with('status', 'Karyawan diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('status', 'Karyawan dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_id')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:100'],
            'division' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
