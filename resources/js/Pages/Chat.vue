<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm} from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { nextTick, onMounted, ref } from 'vue';

defineProps({
    user_id: Number,
    messages: Array,
    room_info: Object
});

const form = useForm({
    send_message: "",
    room_id: null
});
const formTextArea = ref();
const toButtonHeight = ref(0);
const preventScroll = ref(0)
const toButtonShow = ref(false);

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
    router.visit(route('chat'), {
        preserveState: true,
        preserveScroll: true
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
</script>

<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ room_info.name }}</h2>
            <input type="hidden" :value="room_info.id" id="roomId">
        </template>

        <div class="py-12" id="chatScroll">
            <!-- <div v-if="messages.length"> -->
                <div v-for="(message, index) in messages" :key="index">
                    <div v-if="index === 0 || message.send_at !== messages[index - 1].send_at">
                        <p class="text-center">{{ message.send_at }}</p>
                    </div>
                    <div class="max-w-100 sm:px-6 lg:px-8 flex" v-bind:class="{'flex-row-reverse' : message.id === user_id}">
                        <div>
                            <p>{{ message.name }}</p>
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6 text-gray-900">
                                    <p class="whitespace-pre-wrap">{{ message.contents }}</p>
                                </div>
                            </div>
                            <p class="text-right">{{ message.send_time}}</p>
                        </div>
                    </div>
                </div>
            <!-- </div> -->
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
