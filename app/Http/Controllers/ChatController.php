<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Http\Requests;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageControl;

class ChatController extends Controller
{
    /**
     * チャット一覧画面の表示
     */
    public function index(Request $request) {
        // チャット一覧を取得
        $message = Message::select(
            'users.id',
            'users.name',
            'messages.contents',
            DB::raw('DATE_FORMAT(messages.created_at, "%Y/%m/%d") as send_at'),
            DB::raw('TIME_FORMAT(messages.created_at, "%k:%i") as send_time'),
        )
        ->leftJoin('users', 'messages.user_id', '=', 'users.id')
        ->orderby('messages.created_at', 'asc')
        ->get();

        return Inertia::render('Chat', [
            'user_id' => Auth::id(),
            'messages' => $message,
        ]);
    }

    /**
     * チャットメッセージの登録
     * 
     * @param Request $request
     */
    public function create(Request $request) {
        // メッセージが空の場合、処理を行わない
        if (empty($request->send_message)) {
            return to_route('chat');
        }
        // 登録処理
        $created_message_id = Message::insertGetId([
            'user_id' => Auth::id(),
            'contents' => $request->send_message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // イベント（Pusher）
        broadcast(new MessageControl($created_message_id))->toOthers();

        return to_route('chat');
    }
}
