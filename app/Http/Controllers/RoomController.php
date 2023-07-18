<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Room;
use App\Models\Participant;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * ルーム一覧画面表示
     */
    public function index() {
        // 参加済みチャットルームを取得
        $participatingRooms = Participant::where('user_id', Auth::id());
        // 各チャットの参加人数を取得
        $roomMembersCount = Participant::select(
            'room_id',
            DB::raw("count('room_id') as members")
        )
        ->groupBy('room_id');
        // チャットルーム一覧を取得
        $roomList = Room::select(
            'rooms.id',
            'rooms.name',
            'rooms.description',
            'rooms.created_at',
            'users.name as created_user',
            'participating_rooms.room_id',
            'room_members_count.members',
            DB::raw(
                "CASE
                    WHEN rooms.created_user = ". Auth::id(). " THEN 1
                    ELSE 0
                END AS is_create"
            )
        )
        ->leftJoinSub($participatingRooms, 'participating_rooms', function($join) {
            $join->on('rooms.id', '=', 'participating_rooms.room_id');
        })
        ->joinSub($roomMembersCount, 'room_members_count', function($join) {
            $join->on('rooms.id', '=', 'room_members_count.room_id');
        })
        ->join('users', 'rooms.created_user', '=', 'users.id')
        ->orderby('rooms.created_at', 'asc')
        ->get();

        return Inertia::render('Room', [
            'roomList' => $roomList
        ]);
    }

    /**
     * チャットルーム参加登録処理
     * 
     * @param Request $request
     */
    public function joinRoom(Request $request) {
        // room_idが空の場合、処理を行わない
        if (empty($request->room_id)) {
            return to_route('room');
        }

        // participantsテーブルに登録
        Participant::insert([
            'room_id' => $request->room_id,
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return to_route('chat', ["room_id" => $request->room_id]);
    }

    /**
     * チャットルーム登録処理
     * 
     * @param Request $request
     */
    public function createRoom(Request $request) {
        // バリデーション
        $validateData = $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        // roomテーブルに登録
        $createdRoomId = Room::insertGetId([
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'created_user' => Auth::id()
        ]);

        // participantsテーブルに登録
        Participant::insert([
            'room_id' => $createdRoomId,
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return to_route('chat', ["room_id" => $createdRoomId]);
    }

    /**
     * チャットルーム詳細更新処理
     * 
     * @param Request $request
     */
    public function updateRoom(Request $request) {
        // バリデーション
        $validateData = $request->validate([
            'room_id' => 'required',
            'description' => 'required'
        ]);

        // roomの詳細を更新
        Room::where('id', $request->room_id)
        ->update([
            'description' => $request->description,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return to_route('room');
    }

    /**
     * チャットルーム削除処理
     * 
     * @param Request $request
     */
    public function deleteRoom($id) {
        // room_idが空の場合、処理を行わない
        if (empty($id)) {
            return to_route('room');
        }

        // 外部キーのため、参加者とメッセージを先に削除する
        Participant::where('room_id', $id)
        ->delete();
        Message::where('room_id', $id)
        ->delete();
        // ルーム削除処理
        Room::where('id', $id)
        ->delete();

        return to_route('room');
    }
}
