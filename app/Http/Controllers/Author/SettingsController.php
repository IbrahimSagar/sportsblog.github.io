<?php

namespace App\Http\Controllers\Author;

use App\User;
use Carbon\Carbon;
use Toastr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
     public function index()
    {
    	return view('author.settings');
    }
    public function updateProfile(Request $request)
    {	
    	$request->validate([
        'name' => 'required|max:255',
        'email' => 'required|email|unique:users',
        'image' => 'required|image'
        ]);
        
        $image = $request->file('image');
        $slug = str_slug($request->name);
        $user = User::findOrFail(Auth::id());
        if (isset($image)) 
        {
            //make unique name
            $currentDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            //check profile directory
            if (!Storage::disk('public')->exists('profile'))
            {
                Storage::disk('public')->makeDirectory('profile');
            }
            //delete old image
            if (Storage::disk('public')->exists('profile/'.$user->image)) 
            {
                Storage::disk('public')->delete('profile/'.$user->image);
            }
            //resige image for profile and upload
            $profile = Image::make($image)->resize(500,500)->stream();
            Storage::disk('public')->put('profile/'.$imageName,$profile);

        }else{
            $imageName = $user->image;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->image = $imageName;    
        $user->about = $request->about;
        $user->save();
        Toastr::success('Profile Successfully Updated','Success');
        return redirect()->back();
    }
    public function updatePassword(Request $request)
    {
    	
    	$this->validate($request,[
    		'old_password' => 'required',
    		'password' => 'required|confirmed'

    	]);

    	$hashdPassword = Auth::user()->password;
    	if (Hash::check($request->old_password,$hashdPassword)) {
    		if (!Hash::check($request->password,$hashdPassword)) {
    			$user = User::find(Auth::id());
    			$user->password = Hash::make($request->password);
    			$user->save();
    			Toastr::success('Password Successfully Changed','Success');
    			Auth::logout();
    			return redirect()->back();
    		}else{
    			Toastr::error('New Password can not be like old password','Error');
    			return redirect()->back();
    		}
    	}else{

    			Toastr::error('Current Password not match','Error');
    			return redirect()->back();
    	}
    }
}
