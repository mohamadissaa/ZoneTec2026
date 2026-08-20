<v-promo-banners :errors="errors">
    <x-admin::shimmer.settings.themes.image-carousel />
</v-promo-banners>

<!-- Promo Banners Vue Component -->
@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-promo-banners-template"
    >
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-x-2.5">
                    <div class="flex flex-col gap-1">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.themes.edit.promo-banners.title')
                        </p>

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.themes.edit.promo-banners.info')
                        </p>
                    </div>

                    <!-- Add Banner Button -->
                    <div
                        class="secondary-button"
                        @click="create"
                    >
                        @lang('admin::app.settings.themes.edit.promo-banners.add-btn')
                    </div>
                </div>

                <template v-for="(deletedBanner, index) in deletedBanners">
                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[deleted_sliders]['+ index +'][image]'"
                        :value="deletedBanner.image"
                    />
                </template>

                <div
                    class="grid pt-4"
                    v-if="promoBanners.banners.length"
                    v-for="(banner, index) in promoBanners.banners"
                >
                    <!-- Hidden Input -->
                    <input
                        type="file"
                        class="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][image]'"
                        :ref="'imageInput_' + index"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][image]'"
                        :value="banner.image"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][surface_type]'"
                        :value="banner.surface_type"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][surface_color]'"
                        :value="banner.surface_color"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][text_color]'"
                        :value="banner.text_color"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][headline]'"
                        :value="banner.headline"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][sub]'"
                        :value="banner.sub"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][cta]'"
                        :value="banner.cta"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][button_color]'"
                        :value="banner.button_color"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][button_text_color]'"
                        :value="banner.button_text_color"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][url]'"
                        :value="banner.url"
                    />

                    <!-- Details -->
                    <div
                        class="flex cursor-pointer justify-between gap-2.5 py-5"
                        :class="{
                            'border-b border-slate-300 dark:border-gray-800': index < promoBanners.banners.length - 1
                        }"
                    >
                        <div class="flex gap-2.5">
                            <!-- Surface preview -->
                            <div
                                class="h-14 w-24 shrink-0 rounded border border-slate-300 bg-cover bg-center dark:border-gray-800"
                                :style="surfacePreview(banner)"
                            ></div>

                            <div class="grid place-content-start gap-1.5">
                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-if="banner.headline"
                                >
                                    @lang('admin::app.settings.themes.edit.promo-banners.headline'):

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ banner.headline }}
                                    </span>
                                </p>

                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-if="banner.cta"
                                >
                                    @lang('admin::app.settings.themes.edit.promo-banners.cta'):

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ banner.cta }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.settings.themes.edit.promo-banners.url'):

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ banner.url }}
                                    </span>
                                </p>

                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-show="banner.surface_type === 'image'"
                                >
                                    @lang('admin::app.settings.themes.edit.image'):

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        <a
                                            :href="'{{ config('app.url') }}/' + banner.image"
                                            :ref="'image_' + index"
                                            target="_blank"
                                            class="text-blue-600 transition-all hover:underline ltr:ml-2 rtl:mr-2"
                                        >
                                            <span :ref="'imageName_' + index">
                                                @{{ banner.image }}
                                            </span>
                                        </a>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="grid place-content-start gap-1 text-right">
                            <div class="flex items-center gap-x-5">
                                <p
                                    class="cursor-pointer text-blue-600 transition-all hover:underline"
                                    @click="edit(banner, index)"
                                >
                                    @lang('admin::app.settings.themes.edit.edit')
                                </p>

                                <p
                                    class="cursor-pointer text-red-600 transition-all hover:underline"
                                    @click="remove(index)"
                                >
                                    @lang('admin::app.settings.themes.edit.delete')
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty Page -->
                <div
                    class="grid justify-center justify-items-center gap-3.5 px-2.5 py-10"
                    v-else
                >
                    <img
                        class="h-[120px] w-[120px] p-2 dark:mix-blend-exclusion dark:invert"
                        src="{{ bagisto_asset('images/empty-placeholders/default.svg') }}"
                        alt="@lang('admin::app.settings.themes.edit.promo-banners.title')"
                    >

                    <div class="flex flex-col items-center gap-1.5">
                        <p class="text-base font-semibold text-gray-400">
                            @lang('admin::app.settings.themes.edit.promo-banners.add-btn')
                        </p>

                        <p class="text-gray-400">
                            @lang('admin::app.settings.themes.edit.promo-banners.info')
                        </p>
                    </div>
                </div>
            </div>

            <x-admin::form v-slot="{ errors, handleSubmit }" as="div">
                <form
                    @submit.prevent="handleSubmit($event, saveBanner)"
                    enctype="multipart/form-data"
                    ref="createBannerForm"
                >
                    <x-admin::modal ref="addBannerModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                <template v-if="! isUpdating">
                                    @lang('admin::app.settings.themes.edit.promo-banners.add-btn')
                                </template>

                                <template v-else>
                                    @lang('admin::app.settings.themes.edit.promo-banners.update-banner')
                                </template>
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.headline')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[headline]"
                                    v-model="selectedBanner.headline"
                                    :placeholder="trans('admin::app.settings.themes.edit.promo-banners.headline')"
                                    :label="trans('admin::app.settings.themes.edit.promo-banners.headline')"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.sub')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="{{ $currentLocale->code }}[sub]"
                                    v-model="selectedBanner.sub"
                                    :placeholder="trans('admin::app.settings.themes.edit.promo-banners.sub')"
                                    :label="trans('admin::app.settings.themes.edit.promo-banners.sub')"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.cta')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[cta]"
                                    v-model="selectedBanner.cta"
                                    :placeholder="trans('admin::app.settings.themes.edit.promo-banners.cta')"
                                    :label="trans('admin::app.settings.themes.edit.promo-banners.cta')"
                                />
                            </x-admin::form.control-group>

                            <!-- Button colours, only relevant once the button has a label -->
                            <div
                                class="flex gap-4"
                                v-show="selectedBanner.cta"
                            >
                                <x-admin::form.control-group class="flex-1">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.themes.edit.button-color')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="color"
                                        name="{{ $currentLocale->code }}[button_color]"
                                        v-model="selectedBanner.button_color"
                                        class="h-10 w-full cursor-pointer appearance-none rounded-md border transition-all hover:border-gray-400 dark:border-gray-800 dark:hover:border-gray-400"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex-1">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.themes.edit.button-text-color')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="color"
                                        name="{{ $currentLocale->code }}[button_text_color]"
                                        v-model="selectedBanner.button_text_color"
                                        class="h-10 w-full cursor-pointer appearance-none rounded-md border transition-all hover:border-gray-400 dark:border-gray-800 dark:hover:border-gray-400"
                                    />
                                </x-admin::form.control-group>
                            </div>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.url')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[url]"
                                    v-model="selectedBanner.url"
                                    :placeholder="trans('admin::app.settings.themes.edit.promo-banners.url')"
                                    :label="trans('admin::app.settings.themes.edit.promo-banners.url')"
                                />
                            </x-admin::form.control-group>

                            <!-- Surface: a flat colour or an uploaded image -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.surface-type')
                                </x-admin::form.control-group.label>

                                <select
                                    name="{{ $currentLocale->code }}[surface_type]"
                                    v-model="selectedBanner.surface_type"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="color">
                                        @lang('admin::app.settings.themes.edit.promo-banners.surface-color')
                                    </option>

                                    <option value="image">
                                        @lang('admin::app.settings.themes.edit.promo-banners.surface-image')
                                    </option>
                                </select>
                            </x-admin::form.control-group>

                            {{--
                                Plain colour inputs rather than the form control
                                component: they carry no validation rules, and the
                                component's `color` branch would bind `v-model` on
                                both the field wrapper and the input itself.
                            --}}
                            <div class="flex gap-4">
                                <x-admin::form.control-group
                                    class="flex-1"
                                    v-show="selectedBanner.surface_type !== 'image'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.themes.edit.promo-banners.surface-color')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="color"
                                        name="{{ $currentLocale->code }}[surface_color]"
                                        v-model="selectedBanner.surface_color"
                                        class="h-10 w-full cursor-pointer appearance-none rounded-md border transition-all hover:border-gray-400 dark:border-gray-800 dark:hover:border-gray-400"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex-1">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.themes.edit.promo-banners.text-color')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="color"
                                        name="{{ $currentLocale->code }}[text_color]"
                                        v-model="selectedBanner.text_color"
                                        class="h-10 w-full cursor-pointer appearance-none rounded-md border transition-all hover:border-gray-400 dark:border-gray-800 dark:hover:border-gray-400"
                                    />
                                </x-admin::form.control-group>
                            </div>

                            <x-admin::form.control-group v-show="selectedBanner.surface_type === 'image'">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.promo-banners.surface-image')
                                </x-admin::form.control-group.label>

                                <div class="hidden">
                                    <x-admin::media.images
                                        ::key="'banner_image_hidden_' + mediaComponentKey"
                                        name="banner_image"
                                        ::uploaded-images='selectedBannerMediaImages'
                                    />
                                </div>

                                <v-media-images
                                    :key="'banner_image_' + mediaComponentKey"
                                    name="banner_image"
                                    :uploaded-images='selectedBannerMediaImages'
                                >
                                </v-media-images>

                                <x-admin::form.control-group.error control-name="banner_image" />

                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.settings.themes.edit.promo-banners.image-size')
                                </p>
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <!-- Save Button -->
                            <button
                                type="button"
                                class="primary-button justify-center"
                                @click="handleSubmit($event, saveBanner)"
                            >
                                @lang('admin::app.settings.themes.edit.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-promo-banners', {
            template: '#v-promo-banners-template',

            props: ['errors'],

            data() {
                return {
                    promoBanners: @json($theme->translate($currentLocale->code)['options'] ?? null),

                    deletedBanners: [],

                    selectedBanner: {},

                    selectedBannerMediaImages: [],

                    selectedBannerOriginalImage: null,

                    mediaComponentKey: 0,

                    selectedBannerIndex: null,

                    isUpdating: false,
                };
            },

            created() {
                if (! this.promoBanners || ! this.promoBanners.banners) {
                    this.promoBanners = { banners: [] };
                }

                this.resetSelectedBanner();
            },

            methods: {
                saveBanner(_, { resetForm, setErrors }) {
                    const formData = new FormData(this.$refs.createBannerForm);
                    const bannerImage = formData.get("banner_image[]");
                    const hasUploadedImage = bannerImage instanceof File && bannerImage.name !== '';

                    try {
                        const bannerData = {
                            headline: formData.get("{{ $currentLocale->code }}[headline]"),
                            sub: formData.get("{{ $currentLocale->code }}[sub]"),
                            cta: formData.get("{{ $currentLocale->code }}[cta]"),
                            url: formData.get("{{ $currentLocale->code }}[url]"),
                            surface_type: this.selectedBanner.surface_type,
                            surface_color: this.selectedBanner.surface_color,
                            text_color: this.selectedBanner.text_color,
                            button_color: this.selectedBanner.button_color,
                            button_text_color: this.selectedBanner.button_text_color,
                        };

                        if (
                            bannerData.surface_type === 'image'
                            && ! this.hasBannerImage(formData, hasUploadedImage)
                        ) {
                            throw new Error("{{ trans('admin::app.settings.themes.edit.promo-banners.surface-required') }}");
                        }

                        const bannerIndex = this.upsertBanner(bannerData);

                        if (hasUploadedImage) {
                            this.setFile(bannerImage, bannerIndex);
                            this.markBannerImageForDeletion();
                        }

                        resetForm();
                        this.resetSelectedBanner();
                        this.$refs.addBannerModal.toggle();

                    } catch (error) {
                        setErrors({
                            banner_image: [error.message],
                        });
                    }
                },

                upsertBanner(bannerData) {
                    if (this.isUpdating) {
                        this.promoBanners.banners[this.selectedBannerIndex] = {
                            ...this.promoBanners.banners[this.selectedBannerIndex],
                            ...bannerData,
                        };

                        return this.selectedBannerIndex;
                    }

                    this.promoBanners.banners.push(bannerData);

                    return this.promoBanners.banners.length - 1;
                },

                markBannerImageForDeletion() {
                    if (! this.isUpdating || ! this.selectedBannerOriginalImage) {
                        return;
                    }

                    this.deletedBanners.push({
                        image: this.selectedBannerOriginalImage,
                    });
                },

                hasBannerImage(formData, hasUploadedImage) {
                    if (hasUploadedImage) {
                        return true;
                    }

                    return Array.from(formData.keys()).some((key) => {
                        return key === 'banner_image[]' || key.startsWith('banner_image[');
                    });
                },

                setFile(file, index) {
                    const dataTransfer = new DataTransfer();

                    dataTransfer.items.add(file);

                    setTimeout(() => {
                        this.$refs['image_' + index][0].href = URL.createObjectURL(file);

                        this.$refs['imageName_' + index][0].innerHTML = file.name;

                        this.$refs['imageInput_' + index][0].files = dataTransfer.files;
                    }, 0);
                },

                /**
                 * Small swatch in the listing so a banner is recognisable
                 * without opening it.
                 */
                surfacePreview(banner) {
                    if (banner.surface_type === 'image' && banner.image) {
                        return { backgroundImage: `url('{{ asset('/') }}${banner.image}')` };
                    }

                    return {
                        backgroundColor: banner.surface_color || '#ffffff',
                        color: banner.text_color || '#000000',
                    };
                },

                remove(index) {
                    this.$emitter.emit('open-confirm-modal', {
                        agree: () => {
                            const banner = this.promoBanners.banners[index];

                            if (! banner) {
                                return;
                            }

                            if (banner.image) {
                                this.deletedBanners.push(banner);
                            }

                            this.promoBanners.banners.splice(index, 1);
                        },
                    });
                },

                create() {
                    this.openBannerModal();
                },

                edit(banner, index) {
                    this.openBannerModal(banner, index);
                },

                openBannerModal(banner = null, index = null) {
                    this.resetSelectedBanner();

                    if (banner) {
                        this.isUpdating = true;
                        this.selectedBannerIndex = index;

                        /**
                         * Spread over the defaults so a banner saved without a
                         * colour still opens with usable colours.
                         */
                        this.selectedBanner = {
                            ...this.selectedBanner,
                            ...Object.fromEntries(
                                Object.entries(banner).filter(([, value]) => value !== null && value !== '')
                            ),
                        };

                        this.selectedBannerOriginalImage = banner.image;
                        this.selectedBannerMediaImages = banner.image
                            ? [{ id: `banner_image_${index}`, url: '{{ asset('/') }}' + banner.image }]
                            : [];
                    }

                    this.mediaComponentKey++;

                    this.$refs.addBannerModal.toggle();
                },

                resetSelectedBanner() {
                    /**
                     * Colour inputs always post a value, so a banner that was
                     * never given one would otherwise arrive as black on black.
                     */
                    this.selectedBanner = {
                        surface_type: 'color',
                        surface_color: '#1754c3',
                        text_color: '#ffffff',
                        button_color: '#ffffff',
                        button_text_color: '#282828',
                    };

                    this.selectedBannerMediaImages = [];
                    this.selectedBannerOriginalImage = null;
                    this.selectedBannerIndex = null;
                    this.isUpdating = false;
                },
            },
        });
    </script>
@endPushOnce
