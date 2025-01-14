<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Rules\AgeMatchesBirthday;
use App\Rules\agematchesbirthdaymember;
use App\Rules\CheckAtLeastOneCheckbox;
use App\Rules\UniqueNameCombination;
use Illuminate\Support\Facades\Log;
use App\Models\Resident;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountApprovalNotification;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Rules\UniqueEmailWithStatus;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function index()
    {
        return view('welcome');
        //return "Hello, this is the welcome page!";
    }

public function login(){
    return view("auth.login");
}
public function onlineservices(){
    return view("auth.onlineservices");
}
public function aboutus(){
    return view("auth.aboutus");
}
public function loginPost(Request $request)
{
    $request->validate([
       'email' => 'required|email',
    'password' => 'required',
], [
    'email.required' => 'Email address is required.',
    'email.email' => 'Please enter a valid email address.',
    'password.required' => 'Password is required.',
]);

    // Retrieve the resident by email
    $resident = Resident::where('email', $request->email)->first();

    // Check if resident exists
    if (!$resident) {
        return response()->json(['error' => 'User not found'], 422);
    }

    // Check if the provided password matches the stored password
    if ($request->password === $resident->password) {
        $status = $resident->status;
        $residentId = $resident->reg_number;

        if ($status == "pending") {
            return response()->json(['error' => 'Your Account is still in process'], 422);
        } elseif ($status == "Resident") {
            // Log the resident in
            Auth::login($resident);
            
            // Set the userId in the session
            $request->session()->put('userId', $residentId);

            // Redirect to the user index page
            return response()->json(['redirect' => route('user.index', ['userId' => $residentId])]);
        } elseif ($status == "Admin") {
            // Log the user in as admin
            Auth::login($resident);

            // Redirect to the admin dashboard
            return response()->json(['success' => 'Login successful', 'redirect' => route('admin.statisticalreport')]);
        } else {
            return response()->json(['error' => 'User not found'], 422);
        }
    } else {
        // If passwords do not match, return error
        return response()->json(['error' => 'Password incorrect'], 422);
    }
}



function register(){
    return view("auth.register");
}

function registerPost(Request $request){
    return view('auth.register');
  
    }
    function step1(Request $request){
  // Define validation rules
  //dump($request->input('options'));
  $rules = [
    'lname' => ['required','regex:/^[a-zA-Z\s ]+$/', new UniqueNameCombination],
    'fname' => ['required','regex:/^[a-zA-Z\s ]+$/', new UniqueNameCombination],
    'mname' => ['nullable','regex:/^[a-zA-Z\s ]+$/', new UniqueNameCombination],
    'ext' => ['nullable','regex:/^[a-zA-Z\s ]+$/', new UniqueNameCombination],
    'address' => 'required|regex:/^[a-zA-Z0-9 .,()-]*$/',
    'household' => 'required',
    'Birth' => 'required',
    'birthday' => 'required|date',
    'age' => ['required', 'numeric', 'min:15', new AgeMatchesBirthday],
    'cnum' => 'required|regex:/[0-9]{2}-[0-9]{3}-[0-9]{4}/',
    'gender' => 'required|in:Male,Female',
    'civil' => 'required|in:Single,Widowed,Married',
    'citizenship' => 'required|regex:/^[a-zA-Z\s ]+$/',
    'occupation' => 'required|regex:/^[a-zA-Z\s ]+$/',
    'email' => ['required', 'email', new UniqueEmailWithStatus],
    'password' => 'required|min:8|regex:/^(?=.*[a-zA-Z0-9 ])(?=.*\d)(?=.*[$@$!%*?&_])[A-Za-z\d$@$!%*?&_]+$/',


];

$messages = [
    'email.unique' => 'The email has already been taken.',
    'lname.required' => 'The last name field is required.',
    'lname.regex' => 'The last name field should contain only letters and spaces.',

    'fname.required' => 'The first name field is required.',
    'fname.regex' => 'The first name field should contain only letters and spaces.',

    'mname.regex' => 'The middle name field should contain only letters and spaces.',

    'ext.regex' => 'The extension field should contain only letters, spaces, dots, and commas.',

    'address.required' => 'The address field is required.',
    'address.regex' => 'The address field should contain only alphanumeric characters, spaces, dots, commas, hyphens, and parentheses.',

    'household.required' => 'The household field is required.',

    'Birth.required' => 'The birth date field is required.',

    'birthday.required' => 'The birthday field is required.',
    'birthday.date' => 'The birthday must be a valid date.',

    'age.required' => 'The age field is required.',
    'age.numeric' => 'The age must be a number.',
    'age.min' => 'The age must be at least :min.',

    'cnum.required' => 'The contact number field is required.',
    'cnum.regex' => 'The contact number must be in the format XX-XXX-XXXX.',

    'gender.required' => 'The gender field is required.',
    'gender.in' => 'The gender must be either Male or Female.',

    'civil.required' => 'The civil status field is required.',
    'civil.in' => 'The civil status must be Single, Widowed, or Married.',

    'citizenship.required' => 'The citizenship field is required.',
    'citizenship.regex' => 'The citizenship field should contain only letters and spaces.',

    'occupation.required' => 'The occupation field is required.',
    'occupation.regex' => 'The occupation field should contain only letters and spaces.',

    'email.required' => 'The email field is required.',
    'email.email' => 'The email must be a valid email address.',

    'password.required' => 'The password field is required.',
    'password.min' => 'The password must be at least :min characters long.',
    'password.regex' => 'The password must contain at least one letter, one number, and one special character.',
];
$validator = Validator::make($request->all(), $rules,$messages);
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
// Validation passed, proceed with your logic
$data = $request->all();

$data_step1 = $validator->validated();
$indicateIf = [];

 if ($request->has('employed') && $request->input('employed') !== null) {
     $indicateIf[] = $request->input('employed');
 }
 
 if ($request->has('unemployed') && $request->input('unemployed') !== null) {
     $indicateIf[] = $request->input('unemployed');
 }
 
 if ($request->has('PWD') && $request->input('PWD') !== null) {
     $indicateIf[] = $request->input('PWD');
 }
 
 if ($request->has('OFW') && $request->input('OFW') !== null) {
     $indicateIf[] = $request->input('OFW');
 }
 
 if ($request->has('soloparent') && $request->input('soloparent') !== null) {
     $indicateIf[] = $request->input('soloparent');
 }
 
 if ($request->has('OSY') && $request->input('OSY') !== null) {
     $indicateIf[] = $request->input('OSY');
 }
 
 if ($request->has('student') && $request->input('student') !== null) {
     $indicateIf[] = $request->input('student');
 }
 
 if ($request->has('OSC') && $request->input('OSC') !== null) {
     $indicateIf[] = $request->input('OSC');
 }
 
 if (empty($indicateIf)) {
     return response()->json(['error' => 'At least one checkbox must be checked'], 400);
 }
 
 // Convert the array of checkbox values into a string
 $indicateIfString = implode(',', $indicateIf);

$checkedCheckboxes = array_filter($data_step1);
    $request->session()->put('step1', $checkedCheckboxes);
// Remove 'proofofowner' from the data since we've stored the file name separately
$request->session()->put('step1', $data_step1);

// Further processing, if needed
return response()->json(['status' => 'success']);
    }
 
 

public function step2(Request $request)
{
    // Define base validation rules with custom error messages
    $baseRules = [
        'owner' => 'required|in:May-Ari,Nangungupahan,Nakatira sa may Ari,Nakikitira sa Nangungupahan,Informal Settler',
        'ownername' => 'required|regex:/^[a-zA-Z\s .,]+$/',
        'numberoffam' => 'required|integer|min:0',
        'living' => 'required|integer|min:1',
        'Num_days' => 'required|in:Days,Months,Years',
        'voterscert' => 'nullable|mimes:png,jpg,jpeg,pdf|max:5120',
    ];

    // Custom error messages
    $customMessages = [
        'owner.required' => 'Please select an owner type.',
        'Num_days.required' => 'Please select a Day type.',
        'owner.in' => 'Invalid owner type selected.',
        'Num_days.in' => 'Invalid Days type selected.',
        'ownername.required' => 'Please enter the owner name.',
        'ownername.regex' => 'Owner name should contain only letters and spaces.',
        'numberoffam.required' => 'Please enter the number of family members.',
        'living.required' => 'Please enter the number of Living in barangay.',
        'numberoffam.integer' => 'Number of family members should be a valid Number.',
        'living.integer' => 'Living in barangay should be a Number.',
        'numberoffam.min' => 'Number of family members should be 0 or more.',
        'living.min' => 'Number of Living in barangay should be at 1 or more.',
        'voterscert.mimes' => 'Unsupported file type. Please upload an image or PDF.',
        'voterscert.max' => 'The file may not be greater than 5MB.',
    ];

    // Get the request data
    $requestData = $request->all();

    // Define validation rules
    $rules = $baseRules;

    if (isset($requestData['owner']) && $requestData['owner'] !== 'May-Ari') {

        $rules['proofofowner'] = 'required|mimes:png,jpg,jpeg,pdf|max:5120';
        $messages = array_merge($customMessages, [
            'proofofowner.required' => 'The letter of ownership is required.',
        ]);
    } else {
        $messages = $customMessages;
    }

    // Validate the incoming data
    $validatedData = Validator::make($requestData, $rules, $messages);
    if ($validatedData->fails()) {
        return response()->json(['errors' => $validatedData->errors()], 422);
    }
     if ($request->hasFile('proofofowner')) {
        $proofofowner = $request->file('proofofowner');
        if ($proofofowner->isValid()) {
            $proofofownerFilename = time() . '_' . $proofofowner->getClientOriginalName();
            $proofofowner->move(public_path('residentprofile'), $proofofownerFilename);
            $request->session()->put('proofofowner', $proofofownerFilename);
            session(['proofofowner' => $proofofownerFilename]);
        } else {
            return response()->json(['error' => 'Invalid Proof of Ownership file.'], 422);
        }
     }

    if ($request->hasFile('voterscert')) {
        $votersCertificate = $request->file('voterscert');
        if ($votersCertificate->isValid()) {
            $votersFilename = time() . '_' . $votersCertificate->getClientOriginalName();
            $votersCertificate->move(public_path('residentprofile'), $votersFilename);
            $request->session()->put('voters_filename', $votersFilename);
            session(['voters_filename' => $votersFilename]);
    
           
        } else {
            return response()->json(['error' => 'Invalid votersCertificate file.'], 422);
        }
    }


    // Store validated data in the session
    $data_step2 = $validatedData->validated();
    unset($data_step2['proofofowner'], $data_step2['voterscert']);
   $request->session()->put('step2', $data_step2); // must not session here the votercert and proofofowner

    // Return success response
    return response()->json(['status' => 'NEXTSTEP']);
}

    
public function thirdStep(Request $request)
{
    // Define validation rules with custom error messages
    $validator = Validator::make($request->all(), [
        'id-file' => 'required|mimes:jpeg,png,jpg|max:5120',
        'id-select' => 'required|in:Drivers License,National ID,Philhealth,SSS,Barangay ID,Student ID',
    ], [
        'id-file.required' => 'Please upload a valid ID image.',
        'id-file.mimes' => 'The ID image must be a file of type: jpeg, png, jpg.',
        'id-file.max' => 'The ID image may not be greater than 5MB.',
        'id-select.required' => 'Please select an ID type.',
        'id-select.in' => 'Invalid ID type.',
    ]);

    // Check if the validation fails
    if ($validator->fails()) {
        // Return a JSON response with the validation errors
        return response()->json(['errors' => $validator->errors()], 422);
    }else{
         // Proceed with file saving and processing if validation passes
        $file = $request->file('id-file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $filePath = storage_path('app/public/images/' . $filename);
        $file->move(storage_path('app/public/images/'), $filename);
        $request->session()->put('valid_id_path', $filename);
        session(['valid_id_path' => $filename]);
        session(['idtype' => $request->input('id-select')]);


        // Run the Python script to check for a face
        $process = new Process(['C:\Users\jayen\AppData\Local\Programs\Python\Python312\python.exe', base_path('python/check_id_face.py'), $filePath]);
        $process->run();

        // Check if the process ran successfully
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Get the output from the Python script
        $output = json_decode($process->getOutput(), true);

        // Return the response back to the front end
        if ($output['status'] == 'success') {
            return response()->json(['message' => 'Face detected in the ID.', 'status' => 'success']);
        } else {
            return response()->json(['message' => 'No face detected in the ID.']);
        }
    }  
}

    
    public function saveFaceScan(Request $request)
    {
        $imageData = $request->input('image');
        $viewType = $request->input('view');
        $validate = new Resident();
        $validate->filePath = $request->session()->get('thirdStep.id-file'); //the valid ID image
    
          // Generate reg_number in the format REG_DATE_AutoIncrement
            $dateToday = now()->format('Ymd');
            $latestResident = Resident::latest('id')->first();
            $lastId = $latestResident ? $latestResident->id : 0;
            $newId = $lastId + 1;
            $regNumber = "REG_{$dateToday}_0{$newId}";

        if ($imageData && $viewType) {
            try {
                // Decode the base64 image data
                $image = str_replace('data:image/png;base64,', '', $imageData);
                $image = str_replace(' ', '+', $image);
                $imageName =  $regNumber . '.png';
                session(['reg_num' => $regNumber]);
                session(['imageName' => $imageName]);
                // Determine the storage path based on the view type
                $directory = "public/dataset/{$viewType}";
    
                // Ensure the directory exists
                Storage::makeDirectory($directory);
    
                // Save the image to the correct directory
                $imagePath = "{$directory}/{$imageName}";
                Storage::put($imagePath, base64_decode($image));
                
                $validIdPath = $request->session()->get('valid_id_path');
                if (!$validIdPath) {
                    return response()->json(['success' => false, 'message' => 'Valid ID not uploaded']);
                }
    
                // Execute the Python script for face detection
                $process = new Process([
                    'C:\Users\jayen\AppData\Local\Programs\Python\Python312\python.exe',
                    base_path('python/face_detection.py'),
                    storage_path('app/' . $imagePath),
                    storage_path('app/public/images/' . $validIdPath)
                ]);
                $process->run();
    
                // Check if the process executed successfully
                if (!$process->isSuccessful()) { 
                    Log::error('Python script failed: ' . $process->getErrorOutput());
                    return response()->json(['success' => false, 'message' => 'Python script execution failed']);
                }
    
                // Get the output from the Python script
                $output = $process->getOutput();
                
                // Log the raw output for debugging
                Log::info('Python script raw output: ' . $output);
    
                // Decode the output
                $outputDecoded = json_decode($output, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error: ' . json_last_error_msg());
                    return response()->json(['success' => false, 'message' => 'Error decoding JSON from Python script']);
                }
    
                if ($outputDecoded['status'] == 'success') {
                    return response()->json(['success' => true, 'image' => $imageName]);
                } else {
                    return response()->json(['success' => false, 'message' => $outputDecoded['message']]);
                }
            } catch (\Exception $e) {
                Log::error('Error in saveFaceScan: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'An error occurred']);
            }
        }
    
        return response()->json(['success' => false, 'message' => 'Invalid data']);
    }
    
    
    
    public function laststep(Request $request)
    {
     

    $get_daysliving = $request->session()->get('step2.living').' '. $request->session()->get('step2.Num_days');
    $regNumber = session('reg_num');
    $imageName = session('imageName');
    $proofofowner = session('proofofowner');
    $voters_filename = session('voters_filename');
    $valid_id_filename = session('valid_id_path');
    $idtype = session('idtype');
    // Create a new Resident instance and store in the database
    $resident = new Resident();
    $resident->reg_number = $regNumber;
    $resident->lname = $request->session()->get('step1.lname');
    $resident->fname = $request->session()->get('step1.fname');
    $resident->mname = $request->session()->get('step1.mname');
    $resident->ext = $request->session()->get('step1.ext');
    $resident->address = $request->session()->get('step1.address');
    $resident->household = $request->session()->get('step1.household');
    $resident->Birth = $request->session()->get('step1.Birth');
    $resident->birthday = $request->session()->get('step1.birthday');
    $resident->age = $request->session()->get('step1.age');
    $resident->cnum = $request->session()->get('step1.cnum');
    $resident->gender = $request->session()->get('step1.gender');
    $resident->civil = $request->session()->get('step1.civil');
    $resident->citizenship = $request->session()->get('step1.citizenship');
    $resident->occupation = $request->session()->get('step1.occupation');
    $resident->email = $request->session()->get('step1.email');
    $resident->password = $request->session()->get('step1.password');
    // Retrieve values from the session
    $indicateIf = [];

    if ($request->has('employed') && $request->input('employed') !== null) {
        $indicateIf[] = $request->input('employed');
    }
    
    if ($request->has('unemployed') && $request->input('unemployed') !== null) {
        $indicateIf[] = $request->input('unemployed');
    }
    
    if ($request->has('PWD') && $request->input('PWD') !== null) {
        $indicateIf[] = $request->input('PWD');
    }
    
    if ($request->has('OFW') && $request->input('OFW') !== null) {
        $indicateIf[] = $request->input('OFW');
    }
    
    if ($request->has('soloparent') && $request->input('soloparent') !== null) {
        $indicateIf[] = $request->input('soloparent');
    }
    
    if ($request->has('OSY') && $request->input('OSY') !== null) {
        $indicateIf[] = $request->input('OSY');
    }
    
    if ($request->has('student') && $request->input('student') !== null) {
        $indicateIf[] = $request->input('student');
    }
    
    if ($request->has('OSC') && $request->input('OSC') !== null) {
        $indicateIf[] = $request->input('OSC');
    }
    
    if (empty($indicateIf)) {
        return response()->json(['error' => 'At least one checkbox must be checked'], 400);
    }
    // Convert the array of checkbox values into a string
    $indicateIfString = implode(',', $indicateIf);
    $resident->indicate_if = $indicateIfString;

    //step2
    $resident->owner_type = $request->session()->get('step2.owner');
    $resident->owner_name = $request->session()->get('step2.ownername');
    $resident->number_of_family = $request->session()->get('step2.numberoffam');
    $resident->daysofliving = $get_daysliving;
    $resident->image_filename = $imageName;
    $resident->voters_filename = $voters_filename;
    $resident->valid_id_filename = $valid_id_filename;
    $resident->proof_of_owner = $proofofowner;
    $resident->IDtype = $idtype;
    $resident->save();

    // Clear the session data if needed
    $request->session()->forget('step1');
    $request->session()->forget('step2');
    $request->session()->forget('thirdStep');


    return response()->json(['status' => 'Complete','reg_number' => $regNumber], 200);
    }
    
    /*$request->validate([
        "sname" => "required",
        "fname" => "required",
        "mname" => "required",
        "email" => "required",
        "password" => "required",
    ]);*/

    /*$user = new User();
    $user->name = $request->sname;
    $user->email = $request->email;
    $user->password = Hash::make($request->password);

    if($user->save()){
        return redirect(route("login"))
        ->with("success","User created Successfully");
    }
    return redirect(route("register"))
    ->with("Error","Failed to created account");*/
    public function checkEmail(Request $request)
{
    $email = $request->input('email');
    $exists = Resident::where('email', $email)->exists();
    $checkreg = Resident::where('email', $email)->first();

    $regnum = $checkreg->reg_number;
    try {
        if ($exists) {
            $token = Str::random(60);
            $subject = "Forget Password Reset";
            $body = new HtmlString("Click the button to change your Password <a href='" . route('password.reset', ['regnum' => $regnum]) . "'>Click Here!</a>");


            // Send email notification using Mailable class
            try {
                Mail::to($email)->send(new AccountApprovalNotification($subject, $body));
                //$request->session()->put('userId', $checkreg);

                return response()->json(['exists' => true]);
            } catch (\Exception $e) {
                Log::error('Exception while sending email: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to send email'], 500);
            }
        } else {
            return response()->json(['exists' => false]);
        }
    } catch (\Exception $e) {
        Log::error('Error checking email: ' . $e->getMessage());
        return response()->json(['error' => 'Internal Server Error'], 500);
    }
}
public function resetPassword(Request $request)
{
    // Validate the request data
    $request->validate([
        'idnumber' => 'required', // You might need to adjust validation rules according to your requirements
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_])[A-Za-z\d@$!%*?&_]+$/',
        ],
        'confirmpassword' => [
            'required',
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_])[A-Za-z\d@$!%*?&_]+$/',
        ], // Ensure confirmpassword matches password
    ], [
        'password.regex' => 'Password must have at least 8 characters, at least 1 uppercase letter, 1 number, and 1 special character.',
        'confirmpassword.regex' => 'Password must have at least 8 characters, at least 1 uppercase letter, 1 number, and 1 special character.',
    ]);

    if($request->password != $request->confirmpassword){
        return response()->json(['error' => 'Passwords does not Match'], 404);
    }
    // Get the user ID or any identifier from the request
    $idnumber = $request->input('idnumber');

    // Retrieve the user based on the identifier
    $user = Resident::where('reg_number', $idnumber)->first();

    if (!$user) {
        // If user not found, return error response
        return response()->json(['error' => 'User not found'], 404);
    }

    // Update the user's password
    $user->password = $request->input('password');
    $user->save();

    // Optionally, you can log in the user after password update
    Auth::login($user);

    // Return a success response
    return response()->json(['message' => 'Password updated successfully']);
    
}


}