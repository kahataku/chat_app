<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RoomDetails from '@/Components/RoomDetails.vue';

defineProps({
    roomList: Array
});

const open = ref(false);

const form = useForm({
    name: "",
    description: ""
});

// チャットルーム登録制御
const submit = () => {
    form.post(route('room.create'));
}

// チャットルーム参加登録制御
const join = (e) => {
    const room_id = e.target.dataset.id;
    router.post(route('room.join'), {room_id: room_id});
}
</script>

<template>
    <Head title="Room" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Room
                <button class="float-right text-sm bg-slate-800 text-white font-bold rounded py-2 px-4" @click="open = true">新規作成</button>
                <Teleport to="body">
                    <div v-if="open" class="fixed bg-slate-100 w-full h-full z-[5] top-0 left-0 opacity-40"></div>
                    <div v-if="open" class="fixed z-50 top-1/2 left-1/2 w-1/2 h-1/2 bg-white -translate-x-1/2 -translate-y-1/2 rounded p-6">
                        <div class="text-right">
                            <button @click="open = false">×</button>
                        </div>
                        <h2 class="text-center font-bold">新規作成</h2>
                        <form @submit.prevent="submit">
                            <div class="mt-4">
                                <InputLabel for="name" value="Name" />

                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                />
                            </div>
                            <div class="mt-4">
                                <InputLabel for="description" value="Description" />

                                <textarea id="description" cols="30" rows="4" class="resize-none w-full mt-1 block" v-model="form.description"></textarea>
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton class="ml-4 text-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    送信
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Teleport>
            </h2>
        </template>

        <div class="py-12">
            <div v-for="room in roomList" :key="room.id">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 my-5">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" >
                        <Link
                            v-if="room.room_id !== null"
                            class="p-6 text-gray-900 w-full h-full inline-block text-lg hover:bg-sky-50"
                            :href="route('chat')"
                            :data="{room_id: room.id}"
                        >
                            {{ room.name }}
                        </Link>
                        <div v-else class="p-6 text-gray-900 w-full h-full inline-block text-lg">
                            {{ room.name }}
                            <button @click="join" :data-id="room.id" class="float-right bg-blue-500 text-white font-bold py-2 px-4 rounded text-xs">参加する</button>
                        </div>
                        <RoomDetails
                            :room_id="room.id"
                            :description="room.description" 
                            :created_user="room.created_user"
                            :is_create="room.is_create"
                            :memmbers="room.members"
                        >
                            <slot />
                        </RoomDetails>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
