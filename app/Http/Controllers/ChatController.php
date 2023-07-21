<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Http\Requests;
use App\Models\Message;
use App\Models\Room;
use App\Models\Participant;
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
            'users.id as user_id',
            DB::raw(
                "CASE
                    WHEN participants.user_id IS NULL THEN 'unknown'
                    ELSE users.name
                END AS user_name"
            ),
            'users.image as user_image',
            'messages.id',
            'messages.contents',
            'messages.is_delete',
            DB::raw('DATE_FORMAT(messages.created_at, "%Y/%m/%d") as send_at'),
            DB::raw('TIME_FORMAT(messages.created_at, "%k:%i") as send_time'),
        )
        ->join('users', 'messages.user_id', '=', 'users.id')
        ->leftJoin('participants', function($join) use($roomId) {
            $join->on('messages.user_id', '=', 'participants.user_id')
            ->where('participants.room_id', $roomId);
        })
        ->where('messages.room_id', $roomId)
        ->orderby('messages.created_at', 'asc')
        ->get();

        // ルーム情報を取得
        $roomInfo = Room::where('id', $roomId)
        ->first();

        // チャットのメンバーを取得
        $members = Participant::select(
            'users.id',
            'users.name',
            'users.image'
        )
        ->join('users', 'participants.user_id', '=', 'users.id')
        ->where('participants.room_id', $roomId)
        ->orderby('participants.created_at', 'asc')
        ->get();

        return Inertia::render('Chat', [
            'user_id' => Auth::id(),
            'messages' => $message,
            'room_info' => $roomInfo,
            'members' => $members
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
            return to_route('chat', ["room_id" => $request->room_id]);
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

    /**
     * チャットメッセージの削除・再表示処理
     * 
     * @param Request $request
     */
    public function deleteMessage(Request $request) {
        // idとルームIDが空の場合、処理を行わない
        if (empty($request->id) || empty($request->room_id)) {
            return to_route('chat', ["room_id" => $request->room_id]);
        }

        // is_deleteを更新
        Message::where('id', $request->id)
        ->update([
            'is_delete' => $request->is_delete,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return to_route('chat', ["room_id" => $request->room_id]);
    }
}
