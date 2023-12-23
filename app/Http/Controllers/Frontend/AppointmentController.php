<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentRegistration;
use App\Repositories\AppointmentRepository;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
  public function show(string $id, AppointmentRepository $appointmentRepository): View
  {
    /** @var \App\Models\Page $appointment */
    $appointment = $appointmentRepository->getById($id);
    if (!$appointment) {
      abort(404);
    }

    SEOTools::setTitle(
      $appointment->title . ' - ' . DateHelper::getLocalDate($appointment->date)->formatLocalized('%d.%m.%Y %H:%M'),
    );

    return view('site.appointment', ['item' => $appointment]);
  }

  public function next(): View
  {
    /** @var \App\Models\Page $appointment */
    $appointment = Appointment::orderBy('date', 'DESC')->first();

    if (!$appointment) {
      abort(404);
    }

    SEOTools::setTitle(
      $appointment->title . ' - ' . DateHelper::getLocalDate($appointment->date)->formatLocalized('%d.%m.%Y %H:%M'),
    );

    return view('site.appointment', ['item' => $appointment]);
  }
  public function registration(
    string $id,
    AppointmentRepository $appointmentRepository,
    Request $request,
  ): RedirectResponse {
    /** @var \App\Models\Page $appointment */
    $appointment = $appointmentRepository->getById($id);

    if (!$appointment) {
      abort(404);
    }

    AppointmentRegistration::create([
      'name' => $request->get('name'),
      'email' => $request->get('email'),
      'appointment_id' => $id,
    ]);

    return back()
      ->with('success', 'success')
      ->withFragment('#registration_form');
  }
}
