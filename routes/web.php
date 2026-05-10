<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Frontend\AgentRegistrationController;
use App\Http\Controllers\Frontend\AirportSearchController;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\FlightController;
use App\Http\Controllers\Frontend\GuestBookingCancellationController;
use App\Http\Controllers\Frontend\GuestBookingLookupController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\RequestDemoController;
use App\Http\Controllers\Frontend\SupportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/request-demo', RequestDemoController::class)->name('request-demo');
Route::get('/support', [SupportController::class, 'support'])->name('support');
Route::get('/about-us', [SupportController::class, 'about'])->name('about');
Route::permanentRedirect('/contact', '/about-us');
Route::get('/agent/register', [AgentRegistrationController::class, 'landing'])->name('agent.register');
Route::get('/agent/register/apply', [AgentRegistrationController::class, 'create'])->name('agent.register.form');
Route::redirect('/agent-network', '/agent/register')->name('agent-network');
Route::redirect('/register/customer', '/register');
Route::redirect('/register/agent', '/agent/register/apply');
Route::post('/register/customer/validate-field', [RegisteredUserController::class, 'validateField'])
    ->middleware(['guest', 'throttle:30,1'])
    ->name('register.customer.validate-field');
Route::post('/agent/register', [AgentRegistrationController::class, 'store'])->middleware('throttle:6,1')->name('agent.register.store');
Route::get('/agent/register/submitted', [AgentRegistrationController::class, 'submitted'])->name('agent.register.submitted');

Route::get('/flights/search', [FlightController::class, 'search'])->name('flights.search');
Route::get('/flights/results', [FlightController::class, 'results'])->name('flights.results');
Route::get('/flights/results/search', [FlightController::class, 'resultsSearchData'])->name('flights.results.search');
Route::get('/flights/results/data', [FlightController::class, 'resultsData'])->name('flights.results.data');
Route::get('/flights/details/{id}', [FlightController::class, 'details'])->name('flights.details');
Route::get('/airports/search', AirportSearchController::class)->middleware('throttle:60,1')->name('airports.search');

Route::match(['get', 'post'], '/booking/passengers', [BookingController::class, 'passengers'])->middleware('throttle:public-booking-submit')->name('booking.passengers');
Route::match(['get', 'post'], '/booking/review', [BookingController::class, 'review'])->middleware('throttle:public-booking-submit')->name('booking.review');
Route::get('/booking/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');
Route::get('/lookup-booking', [GuestBookingLookupController::class, 'showLookupForm'])->name('booking.lookup');
Route::post('/lookup-booking', [GuestBookingLookupController::class, 'lookup'])->middleware('throttle:lookup-booking')->name('lookup-booking.submit');
Route::get('/guest/bookings/{booking}/access/{token}', [GuestBookingLookupController::class, 'showGuestBooking'])->name('guest.bookings.show');
Route::get('/guest/documents/{bookingDocument}/download', [GuestBookingLookupController::class, 'downloadGuestDocument'])->name('guest.documents.download');
Route::post('/guest/bookings/{booking}/access/{token}/payment-proof', [GuestBookingLookupController::class, 'submitGuestPaymentProof'])->middleware('throttle:payment-proof-submit')->name('guest.bookings.payment-proof');
Route::post('/guest/bookings/{booking}/access/{token}/cancellations', [GuestBookingCancellationController::class, 'store'])->middleware('throttle:guest-token')->name('guest.bookings.cancellations.store');

Route::get('/dashboard', DashboardRedirectController::class)->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
