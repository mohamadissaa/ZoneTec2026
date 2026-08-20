<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.brands.index.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.catalog.brands.index.before') !!}

    {{--
        Brands hang off the `brand` attribute's options. Without that attribute
        there is nothing to manage, so say so rather than showing an empty grid
        whose create button could never work.
    --}}
    @if (! $attribute)
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.catalog.brands.index.title')
            </p>
        </div>

        <div class="mt-8 rounded border border-dashed border-gray-300 p-6 text-center text-gray-600 dark:border-gray-800 dark:text-gray-300">
            @lang('admin::app.catalog.brands.index.missing-attribute')
        </div>
    @else
        <v-brands>
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    @lang('admin::app.catalog.brands.index.title')
                </p>

                <div class="flex items-center gap-x-2.5">
                    @if (bouncer()->hasPermission('catalog.brands.create'))
                        <button
                            type="button"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.brands.index.create-btn')
                        </button>
                    @endif
                </div>
            </div>

            <!-- DataGrid Shimmer -->
            <x-admin::shimmer.datagrid />
        </v-brands>
    @endif

    {!! view_render_event('bagisto.admin.catalog.brands.index.after') !!}

    @if ($attribute)
        @pushOnce('scripts')
            <script
                type="text/x-template"
                id="v-brands-template"
            >
                <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.brands.index.title')
                    </p>

                    <div class="flex items-center gap-x-2.5">
                        <!-- Brand Create Button -->
                        @if (bouncer()->hasPermission('catalog.brands.create'))
                            <button
                                type="button"
                                class="primary-button"
                                @click="isEditing = false; resetForm(); $refs.brandUpdateOrCreateModal.toggle()"
                            >
                                @lang('admin::app.catalog.brands.index.create-btn')
                            </button>
                        @endif
                    </div>
                </div>

                <x-admin::datagrid
                    :src="route('admin.catalog.brands.index')"
                    ref="datagrid"
                >
                    <!-- DataGrid Body -->
                    <template #body="{
                        isLoading,
                        available,
                        applied,
                        selectAll,
                        sort,
                        performAction
                    }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.body />
                        </template>

                        <template v-else>
                            <div
                                v-for="record in available.records"
                                class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950"
                                :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                            >
                                <!-- ID -->
                                <p>@{{ record.id }}</p>

                                <!-- Logo -->
                                <div>
                                    {{--
                                        Height-only sizing keeps the aspect
                                        ratio of wide wordmarks without needing
                                        `object-contain`, which the shipped
                                        admin stylesheet does not carry.
                                    --}}
                                    <img
                                        class="h-10 w-auto max-w-full rounded border border-gray-200 dark:border-gray-800"
                                        v-if="record.logo"
                                        :src="record.logo"
                                        :alt="record.name"
                                    />

                                    <span
                                        class="text-xs text-gray-400"
                                        v-else
                                    >
                                        @lang('admin::app.catalog.brands.index.datagrid.no-logo')
                                    </span>
                                </div>

                                <!-- Name -->
                                <p>@{{ record.name }}</p>

                                <!-- Sort Order -->
                                <p>@{{ record.sort_order }}</p>

                                <!-- Actions -->
                                <div class="flex justify-end">
                                    @if (bouncer()->hasPermission('catalog.brands.edit'))
                                        <a @click="editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                            <span
                                                :class="record.actions.find(action => action.index === 'edit')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                            >
                                            </span>
                                        </a>
                                    @endif

                                    @if (bouncer()->hasPermission('catalog.brands.delete'))
                                        <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                            <span
                                                :class="record.actions.find(action => action.index === 'delete')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                            >
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </template>
                    </template>
                </x-admin::datagrid>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="modalForm"
                >
                    <form
                        @submit="handleSubmit($event, updateOrCreate)"
                        ref="brandForm"
                    >
                        {!! view_render_event('bagisto.admin.catalog.brands.create_form_controls.before') !!}

                        <x-admin::modal ref="brandUpdateOrCreateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    <span v-if="isEditing">
                                        @lang('admin::app.catalog.brands.index.edit.title')
                                    </span>

                                    <span v-else>
                                        @lang('admin::app.catalog.brands.index.create.title')
                                    </span>
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                    v-model="brand.id"
                                />

                                <!-- Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.brands.index.create.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="name"
                                        name="name"
                                        rules="required"
                                        v-model="brand.name"
                                        :label="trans('admin::app.catalog.brands.index.create.name')"
                                        :placeholder="trans('admin::app.catalog.brands.index.create.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Sort Order -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.brands.index.create.sort-order')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        id="sort_order"
                                        name="sort_order"
                                        rules="numeric|min_value:0"
                                        v-model="brand.sort_order"
                                        :label="trans('admin::app.catalog.brands.index.create.sort-order')"
                                        :placeholder="trans('admin::app.catalog.brands.index.create.sort-order')"
                                    />

                                    <x-admin::form.control-group.error control-name="sort_order" />
                                </x-admin::form.control-group>

                                <!-- Logo -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.brands.index.create.logo')
                                    </x-admin::form.control-group.label>

                                    {{--
                                        Rendered once, hidden, purely so the
                                        component pushes its template and Vue
                                        definition; the visible one below binds
                                        `uploaded-images` to component state,
                                        which the Blade component cannot do.
                                    --}}
                                    <div class="hidden">
                                        <x-admin::media.images
                                            name="logo"
                                            ::uploaded-images='brand.image'
                                        />
                                    </div>

                                    <v-media-images
                                        name="logo"
                                        :uploaded-images='brand.image'
                                    >
                                    </v-media-images>

                                    <x-admin::form.control-group.error control-name="logo" />
                                </x-admin::form.control-group>

                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.catalog.brands.index.create.logo-info')
                                </p>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <!-- Save Button -->
                                <x-admin::button
                                    button-type="button"
                                    class="primary-button"
                                    :title="trans('admin::app.catalog.brands.index.create.save-btn')"
                                    ::loading="isLoading"
                                    ::disabled="isLoading"
                                />
                            </x-slot>
                        </x-admin::modal>

                        {!! view_render_event('bagisto.admin.catalog.brands.create_form_controls.after') !!}
                    </form>
                </x-admin::form>
            </script>

            <script type="module">
                app.component('v-brands', {
                    template: '#v-brands-template',

                    data() {
                        return {
                            brand: {
                                image: [],
                            },

                            isLoading: false,

                            isEditing: false,
                        }
                    },

                    computed: {
                        gridsCount() {
                            let count = this.$refs.datagrid.available.columns.length;

                            if (this.$refs.datagrid.available.actions.length) {
                                ++count;
                            }

                            if (this.$refs.datagrid.available.massActions.length) {
                                ++count;
                            }

                            return count;
                        },
                    },

                    methods: {
                        updateOrCreate(params, { resetForm, setErrors }) {
                            this.isLoading = true;

                            let formData = new FormData(this.$refs.brandForm);

                            if (params.id) {
                                formData.append('_method', 'put');
                            }

                            this.$axios.post(
                                params.id
                                    ? "{{ route('admin.catalog.brands.update', 'replace-id') }}".replace('replace-id', params.id)
                                    : "{{ route('admin.catalog.brands.store') }}",
                                formData,
                                {
                                    headers: {
                                        'Content-Type': 'multipart/form-data'
                                    }
                                }
                            )
                            .then((response) => {
                                this.isLoading = false;

                                this.$refs.brandUpdateOrCreateModal.close();

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                this.$refs.datagrid.get();

                                resetForm();
                            })
                            .catch(error => {
                                this.isLoading = false;

                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                                }
                            });
                        },

                        editModal(url) {
                            this.$axios.get(url)
                                .then((response) => {
                                    this.brand = {
                                        ...response.data.data,
                                        image: response.data.data.logo_url
                                            ? [{ id: 'logo_url', url: response.data.data.logo_url }]
                                            : [],
                                    };

                                    this.isEditing = true;

                                    this.$refs.brandUpdateOrCreateModal.toggle();
                                });
                        },

                        resetForm() {
                            this.brand = {
                                image: [],
                            };
                        },
                    },
                });
            </script>
        @endPushOnce
    @endif
</x-admin::layouts>
