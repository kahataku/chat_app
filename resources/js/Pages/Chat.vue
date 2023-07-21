<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm} from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { nextTick, onMounted, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    user_id: Number,
    messages: Array,
    room_info: Object,
    members: Array
});

const form = useForm({
    send_message: "",
    room_id: null
});
const formTextArea = ref();
const toButtonHeight = ref(0);
const preventScroll = ref(0)
const toButtonShow = ref(false);
const imageBasePath = "../../../../storage/app/";
const noImage = "person.png"

onMounted(() => {
    toButtonHeight.value = formTextArea.value.offsetHeight + 'px';
    nextTick(() => {
        document.getElementById('chatScroll').scrollIntoView(false);
        preventScroll.value = window.scrollY;
    });
    window.addEventListener("scroll", showToButton);
});

// リスナー（Pusher）
window.Echo.channel('chat').listen('MessageControl', function(data) {
    const room_id = document.getElementById("roomId").value;
    router.visit(route('chat'), {
        data: {room_id: room_id},
        preserveState: true,
        onSuccess: () => {
            scrollEnd();
            preventScroll.value = window.scrollY;
        }
    });
});

// スクロールボタン制御
const showToButton = () => {
    toButtonShow.value = preventScroll.value !== window.scrollY;
}

// 一番下までスクロールする
const scrollEnd = () => {
    document.getElementById('chatScroll').scrollIntoView(false);
}

// 送信制御
const submit = () => {
    form.room_id = document.getElementById("roomId").value;
    form.post(route('chat.create'), {
        onSuccess: () => {
            form.reset();
            scrollEnd();
            preventScroll.value = window.scrollY;
        }
    });
}

// メッセージ編集要素を表示
const showEditChatButton = (e) => {
    const edit_chat_button = e.target.firstElementChild;
    if (edit_chat_button.classList.contains("hidden")) {
        edit_chat_button.classList.remove("hidden");
    }
}

// メッセージ編集要素を非表示
const hideEidtChatButton = (e) => {
    e.target.firstElementChild.classList.add("hidden");
}

// メッセージ削除・再表示データ送信処理
const changeIsDelete = (message_id, is_delete) => {
    const room_id = document.getElementById("roomId").value;
    router.put(route('chat.delete'), {
        id: message_id,
        room_id: room_id,
        is_delete: is_delete
    }, {
        onSuccess: () => {
            scrollEnd();
            preventScroll.value = window.scrollY;
        }
    });
}

// メッセージ削除処理
const deleteMessage = (e) => {
    const message_id = e.target.dataset.id;
    changeIsDelete(message_id, 1);
}

// メッセージ再表示処理
const showMessage = (e) => {
    const message_id = e.target.dataset.id;
    changeIsDelete(message_id, 0);
}

const withdraw = () => {
    const room_id = document.getElementById("roomId").value;
    router.delete(route('room.withdraw', {id: room_id}));
}
</script>

<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between item-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ room_info.name }}</h2>
                <input type="hidden" :value="room_info.id" id="roomId">
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <div class="ml-3 relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        メンバー：{{ members.length }}人
                                        <svg
                                            class="ml-2 -mr-0.5 h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <div v-for="member in members" :key="member.id" class="flex">
                                    <img :src="imageBasePath + (member.image == null ? noImage : member.image)" class="w-8 h-8 object-cover mx-4 my-2 rounded-full">
                                    <p class="px-2 py-2 text-gray-700">{{ member.name }}</p>
                                </div>
                                <button v-if="room_info.created_user !== user_id" @click="withdraw" class="px-4 py-2 text-red-600 border-t border-solid border-slate-200 w-full text-sm">退会する</button>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12" id="chatScroll">
            <div v-for="(message, index) in messages" :key="index">
                <p v-if="index === 0 || message.send_at !== messages[index - 1].send_at" class="text-center mb-2">{{ message.send_at }}</p>
                <template v-if="message.is_delete">
                    <div class="sm:px-6 lg:px-8 mb-4 flex justify-center item-center">
                        <p class="text-center">{{ message.user_name }}がメッセージを削除しました</p>
                        <button v-if="message.user_id === user_id" class="ml-5 text-xs text-sky-500" :data-id="message.id" @click="showMessage">取消</button>
                    </div>
                </template>
                <template v-else>
                    <div class="max-w-100 sm:px-6 lg:px-8 mb-4 flex" v-bind:class="{'flex-row-reverse' : message.user_id === user_id}">
                        <div class="relative">
                            <div v-if="message.user_id !== user_id" class="flex mb-1">
                                <img :src="imageBasePath + (message.user_image == null || message.user_name === 'unknown' ? noImage : message.user_image)" class="w-8 h-8 object-cover rounded-full mr-2 border">
                                <p>{{ message.user_name }}</p>
                            </div>
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <template v-if="message.user_id === user_id">
                                    <div
                                        class="p-6 text-gray-900 whitespace-pre-wrap"
                                        @mouseenter="showEditChatButton"
                                        @mouseleave="hideEidtChatButton"
                                    >
                                        {{ message.contents }}
                                        <div class="absolute -top-4 right-0 text-xs hidden text-red-600">
                                            <button @click="deleteMessage" :data-id="message.id">削除</button>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="p-6 text-gray-900 whitespace-pre-wrap">{{ message.contents }}</div>
                                </template>
                            </div>
                            <p class="text-right">{{ message.send_time}}</p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="fixed bottom-0 w-full py-1.5" ref="formTextArea">
                <form @submit.prevent="submit" class="sm:px-6 lg:px-8 flex items-center">
                    <textarea class="w-11/12 resize-none" cols="10" rows="1" v-model="form.send_message"></textarea>
                    <PrimaryButton class="ml-4 text-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        送信
                    </PrimaryButton>
                </form>
            </div>
            <button v-show="toButtonShow" @click="scrollEnd" class="fixed bottom-0 right-0 bg-blue-500 text-white font-bold rounded-full px-1.5 mr-6" :style="{bottom: toButtonHeight}">↓</button>
        </div>
    </AuthenticatedLayout>
</template>
