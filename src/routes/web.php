<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GFileController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\TerritoryController;
use App\Http\Controllers\ParishBookController;

//////////// MAIN PAGE ROUTES ////////////

// Welcome route only for not authorized users
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome')->middleware('guest');

Route::get('/', [GFileController::class, 'index'])->name('mainPage')->middleware('auth');


//////////// USER ROUTES ////////////

/// REGISTER ///

// Register page
Route::get('/register', [UserController::class, 'showRegister'])->name('register')->middleware('guest');

// Submiting form
Route::post('/registerCheckNick', [UserController::class, 'check_for_nick'])->name('registerCheckNick');

// Creating new user and saving him
Route::post('/registerSubmit', [UserController::class, 'store'])->name('registerSubmit');

/// LOGIN ///

// Login page
Route::get('/login', [UserController::class, 'showLogin'])->name('login')->middleware('guest');

// Log user in
Route::post('/loginSubmit', [UserController::class, 'authenticate'])->name('loginSubmit');

/// LOGOUT ///

// Log user out
Route::get('/logout', [UserController::class, 'logout'])->name('logout')->middleware('auth');

/// FORGOTTEN PASSWORD ///

Route::get('/forgot/reset/{token}', [UserController::class, 'showResetForm'])->name('resetForm');

Route::post('/forgot/reset', [UserController::class, 'resetPassword'])->name('reset');

Route::post('/forgot/setNew', [UserController::class, 'setNewPassword'])->name('setNew');

Route::get('/forgot', [UserController::class, 'forgot'])->name('forgot');


/// USER PROFILE ///

Route::get('/profile', [UserController::class, 'showProfile'])->name('showProfiled')->middleware('auth');

Route::post('/changePasswordSubmit', [UserController::class, 'changePassword'])->name('changePasswordSubmit')->middleware('auth');


//////////// FILE ROUTES ////////////

// Uploading file
Route::post('/uploadSubmit', [GFileController::class, 'store'])->name('submitFile')->middleware('auth');

// Executing parser script
Route::post('/startParser', [GFileController::class, 'executeParser'])->name('executeParser')->middleware('auth');

// Executing matcher script
Route::post('/startMatcher', [GFileController::class, 'executeMatcher'])->name('executeMatcher')->middleware('auth');

// fetching map of territories
Route::post('/fetchTerritories', [TerritoryController::class, 'fetchTerritories'])->name('fetchTerritories');

// Assigning territories to families/people
Route::post('/assignTerritories', [TerritoryController::class, 'assignTerritories'])->name('assignTerritories');

Route::delete('/deleteFile', [GFileController::class, 'delete'])->name('deleteFile')->middleware('auth');


//////////// RECORD ROUTES ////////////

Route::get('/{file}/records', [RecordController::class, 'index'])->name('recordIndex')->middleware('auth');

Route::get('/record/{record}', [RecordController::class, 'show'])->name('recordDetail')->middleware('auth');

// Fetch more records
Route::get('/getRecords', [RecordController::class, 'get_records'])->name('getRecords')->middleware('auth');

// Delete record
Route::delete('/deleteRecord', [RecordController::class, 'delete'])->name('deleteRecord')->middleware('auth');

Route::delete('/deleteBookSuggestion', [RecordController::class, 'delete_book_suggestion'])->name('deleteBookSuggestion')->middleware('auth');



//////////// PARISHBOOK ROUTES ////////////

Route::get('/parishBooks', [ParishBookController::class, 'show'])->name('parishBooks')->middleware('auth');

Route::get('/getBook', [ParishBookController::class, 'get_book'])->name('getBook')->middleware('auth');

Route::post('/addBook', [ParishBookController::class, 'add_book'])->name('addBook')->middleware('auth');

Route::delete('/deleteBook', [ParishBookController::class, 'delete_book'])->name('deleteBook')->middleware('auth');


//////////// TAG ROUTES ////////////

Route::get('/getPerson', [PersonController::class, 'get_person'])->name('getPerson')->middleware('auth');

Route::get('/getFamily', [FamilyController::class, 'get_family'])->name('getFamily')->middleware('auth');

Route::post('/addPerson', [PersonController::class, 'add_person'])->name('addPerson')->middleware('auth');

Route::delete('/deletePerson', [PersonController::class, 'delete_person'])->name('deletePerson')->middleware('auth');

Route::post('/addFamily', [FamilyController::class, 'add_family'])->name('addFamily')->middleware('auth');

Route::delete('/deleteFamily', [FamilyController::class, 'delete_family'])->name('deleteFamily')->middleware('auth');


//////////// NOTES ROUTES ////////////

Route::get('/notes', [NoteController::class, 'index'])->name('notes')->middleware('auth');

Route::get('/noteExists', [NoteController::class, 'check_if_exists'])->name('noteExists')->middleware('auth');

Route::get('/fetchNote', [NoteController::class, 'fetch'])->name('fetchNote')->middleware('auth');

Route::post('/createNote', [NoteController::class, 'store'])->name('createNote')->middleware('auth');

Route::put('/updateNote', [NoteController::class, 'update'])->name('updateNote')->middleware('auth');

Route::delete('/deleteNote', [NoteController::class, 'delete'])->name('deleteNote')->middleware('auth');

//////////// GUIDE ROUTES ////////////

Route::get('/guide', function(){return view('guide/guide');})->name('guide')->middleware('auth');

