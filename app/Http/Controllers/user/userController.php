<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVerify;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isNull;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login()
    {
        return view("user.login");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function doLogin(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ],[
            'email.required' => 'Email wajib diisi',
            'password.required' => 'Password wajib diisi'
        ]);

        $data = [
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ];

        if(Auth::attempt($data)){
            return redirect()->route('home')->with('success', 'Selamat datang!');
        }
        else{
            return redirect()->route('login')->withErrors('Email dan Password tidak sesuai')->withInput();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('login')->with('success','Logged Out');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function create()
    {
        //
        return view('user.insert');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function store(Request $request)
    {
        //dd($request);
        $request->validate([
            'email' =>'required|string|email:rfc,dns|max:100|unique:users,email',
            'name' =>'required|min:5|max:25',
            'level' =>'required|string',
            'password' => 'nullable|string|min:5',
            'password_confirmation'=>'required_with:password|same:password'
        ],[
            'email.required'=>'Email harus diisi',
            'email.string'=>'Email harus string',
            'email.max'=>'Email maksimal 100 karakter',
            'email.email'=>'Format email harus valid',
            'name.required'=>'Nama harus diisi',
            'name.unique'=>'Email sudah terdaftar',
            'name.min'=>'Minimum kartakter 5',
            'name.max'=>'Maximum kartakter 25',
            'level.required' =>'Level harus diisi',
            'password.min'=>'Minimum kartakter 5',
            'password.string'=>'Password harus string',
            'password_confirmation.required_with'=>'Konfirmasi password harus diisi',
            'password_confirmation.same'=>'Konfirmasi password berbeda',
        ]);

        $data = [
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'level'=>$request->input('level'),
            'password'=>bcrypt($request->input('password'))
        ];

        // Untuk BigQuery, generate ID manual
        if (config('database.default') === 'bigquery') {
            $data['id'] = Str::uuid()->toString();
        }

        User::create($data);

        $cekToken = UserVerify::where('email', $request->input('email'))->first();

        if($cekToken){
            UserVerify::where('email', $request->input('email'))->delete();
        }

        $token = Str::uuid();
        
        $data_token = [
            'email' => $request->input('email'),
            'token' => $token
        ];

        UserVerify::create($data_token);

        // Mail::send('user.email', ['token' => $token], function($message) use ($request){
        //     $message->to($request->input('email'));
        //     $message->subject('Verifikasi Email');
        // });
            
        return redirect()->route('user.insert')->with('success', 'User berhasil ditambahkan, Email telah dikirimkan, Silahkan dicek');

    }

    /**
     * Update the specified resource in storage.
     */
    public function updateData(Request $request)
    {        
        return view('user.updateData');
    }

    public function doUpdateData(Request $request)
    {
        $request->validate([
            'name' =>'required|min:5|max:25',
            'level' =>'required',
            'password' => 'nullable|string|min:5',
            'password_confirmation'=>'required_with:password|same:password'
        ],[
            'name.required'=>'Nama harus diisi',
            'name.min'=>'Minimum kartakter 5',
            'name.max'=>'Maximum kartakter 25',
            'level.required' =>'Level harus diisi',
            'password.min'=>'Minimum kartakter 5',
            'password.string'=>'Password harus string',
            'password_confirmation.required_with'=>'Konfirmasi password harus diisi',
            'password_confirmation.same'=>'Konfirmasi password berbeda',
        ]);

        $data = [
            'name'=>$request->input('name'),
            'level'=>$request->input('level'),
            'password'=>$request->input('password') ? bcrypt($request->input('password')) : Auth::user()->password
        ];

        User::where('id', Auth::user()->id)->update($data);

        return redirect()->route('login')->with('success','Update data berhasil');
    }

    public function edit()
    {
        //
       // return view('user.insert');
    }

    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            
            // Gunakan Eloquent untuk query
            $query = User::query();
            
            // Tambahkan kondisi pencarian jika ada
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhere('email', 'like', '%'.$search.'%');
                });
            }
            
            // Urutkan berdasarkan yang terbaru dan pagination dengan 10 item per halaman
            $users = $query->orderBy('created_at', 'desc')->paginate(10);
            
            // Format tanggal untuk setiap user
            $users->getCollection()->transform(function ($user) {
                $user->formatted_created_at = Carbon::parse($user->created_at)->format('d/m/Y H:i');
                $user->formatted_updated_at = Carbon::parse($user->updated_at)->format('d/m/Y H:i');
                return $user;
            });
            
            return view('user.view', compact('users', 'search'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data user: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    function verifyAccount($token){
        $cekuser = UserVerify::where('token',$token)->first();
        if(!is_null($cekuser)){
            $email = $cekuser->email;
            $datauser = User::where('email',$email)->first();
            if($datauser->email_verified_at){
                $message = "Akun telah terverifikasi sebelumnya";
            }else{
                $data = [
                    'email_verified_at' => Carbon::now()
                ];

                User::where('email', $email)->update($data);
                UserVerify::where('email', $email)->delete();

                $message = "Akun telah terverifikasi, silahkan login";
            }

            return redirect()->route('login')->with('success',$message);
        }else{
            return redirect()->route('login')->withErrors('Link token tidak valid');
        }        
    }

    public function destroy(string $id)
    {
        //
        try {
            // For BigQuery, use soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('users')
                    ->where('id', $id)
                    ->update(['deleted_at' => now()]);
            } else {

                $user = User::where('id',$id)->first();

                $affected = DB::table('users')
                            ->where('id', $id)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('user.view')
                ->with('success', 'User berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus user: '.$e->getMessage());
        }
    }
}