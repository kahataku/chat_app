<script setup>
import { router, useForm } from "@inertiajs/vue3";
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from "vue";

const isOpenDetails = ref(false);
const isEdit = ref(false);

const props = defineProps([
    'room_id',
    'description',
    'created_user',
    'is_create',
    'memmbers'
]);

const form = useForm({
    room_id: props.room_id,
    description: props.description
});

// ルーム登録処理
const submit = () => {
    form.put(route('room.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isEdit.value = false;
        }
    });
}

// ルーム削除処理
const deleteRoom = () => {
    router.delete(route('room.delete', {id: props.room_id}));
}
</script>

<template>
    <div class="px-6 pb-6 pt-2 text-xs">
        <div class="flex items-center justify-between">
            <button @click="isOpenDetails = !isOpenDetails" class="text-gray-400">
                詳細<span v-if="isOpenDetails">非</span>表示
            </button>
            <p>参加人数：{{ memmbers }}</p>
        </div>

        <div v-if="isOpenDetails" class="mt-4">
            <div v-if="is_create" class="text-right mb-2">
                <button @click="isEdit = !isEdit" class="text-grey-600 text-xs mr-4">
                    <template v-if="isEdit">
                        キャンセル
                    </template>
                    <template v-else>
                        編集する
                    </template>
                </button>
                <button v-if="!isEdit" @click="deleteRoom" class="text-red-500">削除する</button>
            </div>
            <div class="p-2 border-t border-solid border-slate-300">
                <p v-if="!isEdit" class="text-sm py-2 px-3 whitespace-pre-wrap">{{ description }}</p>
                <div v-else>
                    <form @submit.prevent="submit">
                        <textarea cols="30" rows="2" class="w-full resize-none" v-model="form.description"></textarea>
                        <div class="flex items-center justify-end mt-2">
                            <PrimaryButton class="ml-4 text-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                OK
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
                <p class="text-right mt-2">作成者：{{ created_user }}</p>
            </div>
        </div>
    </div>
</template>
