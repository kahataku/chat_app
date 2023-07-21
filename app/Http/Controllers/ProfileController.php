<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * プロフィール画像登録処理
     * TODO: ユーザ情報変更時に本処理を移行できるようにする
     * 
     * @param Request $request
     */
    public function fileUpload(Request $request)
    {
        // fileが空の場合、プロフィール編集画面へ遷移
        if (empty($request->file())) {
            return Redirect::route('profile.edit');
        }
        // 元のプロフィール画像がnullでない場合、削除する
        if (!is_null($request->user()->image)) {
            Storage::delete($request->user()->image);
        }
        // プロフィール画像を保存
        $imagePath = $request->file('imageFile')->store('public/users/'. Auth::id());
        // users.imageを変更
        $request->user()->image = $imagePath;
        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
