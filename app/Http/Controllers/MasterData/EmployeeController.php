<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ItAsset\AssetAssignment;
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
        Employee::create($this->validateData($request));

        return back()->with('status', 'Karyawan ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validateData($request, $employee->id));

        return back()->with('status', 'Karyawan diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        // BR-32: an employee with an ACTIVE asset assignment cannot be deleted.
        if (AssetAssignment::where('employee_id', $employee->id)->whereNull('returned_at')->exists()) {
            return back()->withErrors(['employee' => 'Karyawan masih memiliki assignment asset aktif; kembalikan dulu atau ubah status jadi nonaktif.']);
        }

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
