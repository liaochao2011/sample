<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail'],
        ]);
        $this->middleware('guest', [
            'only' => ['create'],
        ]);
    }
    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function followings(User $user)
    {
        $users = $user->followings()->paginate(30);
        $title = "关注的人";
        return view('users.show_follow', compact('users', 'title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(30);
        $title = "粉丝";
        return view('users.show_follow', compact('users', 'title'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->statuses()
            ->orderBy('created_at', 'desc')
            ->paginate(30);
        return view('users.show', compact('user', 'statuses'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required|max:50',
            'email'    => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $this->sendConfirmMailTo($user);
        session()->flash('success', 'confirm mail has been send to you mailbox,please check it!');
        return redirect('/');
    }
    public function sendConfirmMailTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        // $from    = 'liaochao2011@foxmail.com';
        $name    = 'liaochao';
        $to      = $user->email;
        $subject = "thank you for register,please confirm you mail box";
        Mail::send($view, $data, function ($message) use ($from, $to, $name, $subject) {
            $message->to($to)->subject($subject);
        });

    }
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->validate($request, [
            'name'     => 'required|max:50',
            'password' => 'nullable|confirmed|min:6',
        ]);
        $this->authorize('update', $user);
        $data['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);
        session()->flash('success', 'your info has been update');
        return redirect()->route('users.show', $user->id);

    }

    public function destroy(User $user)
    {

        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated        = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', 'congratulation to you ,you account has been activated!');
        return redirect()->route('users.show', [$user]);
    }
}
