<?php

namespace App\Controllers;

use App\Models\Employee;

/**
 * @property Employee employee
 */
class Office extends Secure_Controller
{
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('office', null, 'office');
    }

    /**
     * @return void
     */
    public function getIndex(): void
{
    $person_id = $this->session->get('person_id');
    $data['allowed_modules'] = model('Module')->get_allowed_home_modules($person_id)->getResult();

    echo view('home/office', $data);
}

    /**
     * @return void
     */
    public function logout(): void
    {
        $this->employee = model(Employee::class);

        $this->employee->logout();
    }
}
