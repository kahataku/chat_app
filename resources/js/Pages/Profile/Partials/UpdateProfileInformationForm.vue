<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const imageUrl = ref();
const imageFile = ref();
const imageBasePath = "../../../../storage/app/";
const noImage = "person.png"

onMounted(() => {
    imageUrl.value = imageBasePath + (user.image == null ? noImage : user.image);
})

// 送信制御
const submit = () => {
    form.patch(route('profile.update'), {
        onFinish: () => {
            router.post(route('profile.fileUpload'), {imageFile: imageFile.value});
        }
    });
}

// ボタン押下時に、input['file']のclickを発火
const selectImage = (e) => {
    const fileInput = document.getElementById('image');
    fileInput.click();
}

// 選択した画像をプレビュー
const uploadFile = (e) => {
    const selectImage = e.target.files[0];
    // 画像選択された場合、プレビューする
    if (selectImage) {
        imageUrl.value = URL.createObjectURL(selectImage);
        imageFile.value = selectImage;
    }
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="props.mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="props.status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div>
                <InputLabel for="image" value="Image" />

                <div class="w-52 h-52 mt-1">
                    <img v-if="imageUrl" :src="imageUrl" class="border rounded object-cover w-full h-full">
                </div>
                <input id="image" type="file" class="hidden" @change="uploadFile" />
                <button type="button" @click="selectImage" class="bg-slate-300 py-2 px-4 rounded text-sm mt-3">画像を選択</button>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
