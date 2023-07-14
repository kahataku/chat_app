<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Http\Requests;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageControl;

class ChatController extends Controller
{
    /**
     * チャット一覧画面の表示
     * 
     * @param Request $request
     */
    public function index(Request $request) {
        $roomId = $request->query('room_id');
        // room_idが空の場合、ルーム一覧画面に遷移
        if (empty($roomId)) {
            return to_route('room');
        }

        // チャット一覧を取得
        $message = Message::select(
            'users.id',
            'users.name',
            'messages.contents',
            DB::raw('DATE_FORMAT(messages.created_at, "%Y/%m/%d") as send_at'),
            DB::raw('TIME_FORMAT(messages.created_at, "%k:%i") as send_time'),
        )
        ->leftJoin('users', 'messages.user_id', '=', 'users.id')
        ->where('messages.room_id', $roomId)
        ->orderby('messages.created_at', 'asc')
        ->get();

        // ルーム情報を取得
        $roomInfo = Room::select(
            'id',
            'name'
        )
        ->where('id', $roomId)
        ->first();

        return Inertia::render('Chat', [
            'user_id' => Auth::id(),
            'messages' => $message,
            'room_info' => $roomInfo
        ]);
    }

    /**
     * チャットメッセージの登録
     * 
     * @param Request $request
     */
    public function create(Request $request) {
        // メッセージとルームIDが空の場合、処理を行わない
        if (empty($request->send_message) || empty($request->room_id)) {
            return to_route('chat');
        }
        // 登録処理
        $created_message_id = Message::insertGetId([
            'user_id' => Auth::id(),
            'contents' => $request->send_message,
            'room_id' => $request->room_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // イベント（Pusher）
        broadcast(new MessageControl($created_message_id))->toOthers();

        return to_route('chat', ["room_id" => $request->room_id]);
    }
}
