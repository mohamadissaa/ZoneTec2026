@php
    /**
     * Primary menu links. These are editorial links rather than the category
     * tree (which lives in the blue CATEGORIES panel), so they are declared
     * here for easy editing. `children` renders a hover dropdown.
     */
    $menuLinks = [
        ['label' => 'Home',      'url' => route('shop.home.index')],
        ['label' => 'Computers', 'url' => url('computers')],
        ['label' => 'Printing',  'url' => url('printing')],
        ['label' => 'Networking', 'url' => url('networking')],
        ['label' => 'New Arrivals', 'url' => url('search?sort=created_at-desc&limit=10')],
        [
            'label'    => 'Customer Service',
            'url'      => url('page/customer-service'),
            'children' => [
                ['label' => 'Contact Us',       'url' => url('contact-us')],
                ['label' => 'Privacy Policy',   'url' => url('page/privacy-policy')],
                ['label' => 'Refund Policy',    'url' => url('page/refund-policy')],
            ],
        ],

        //['label' => 'Contact Us',      'url' => url('contact-us')],
        ['label' => 'About Us',        'url' => url('page/about-us')],
    ];
@endphp

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.nav.before') !!}

<div class="w-full border-b border-zonetec-border bg-white">
    <div class="flex items-stretch gap-x-10 px-[60px] max-1180:gap-x-6 max-1180:px-8">
        {{-- Category panel trigger: full category tree on hover --}}
        <v-zonetec-category class="shrink-0"></v-zonetec-category>

        {{--
            Single row, always. The link list is wide enough that `flex-wrap`
            drops the last item ("About Us") onto a second row at anything under
            ~1260px, so the gap tightens by breakpoint instead of wrapping.
        --}}
        <nav class="flex grow flex-nowrap items-center gap-x-9 py-3 max-2xl:gap-x-6 max-1180:gap-x-4">
            @foreach ($menuLinks as $link)
                <div class="group relative shrink-0">
                    <a
                        href="{{ $link['url'] }}"
                        class="flex items-center gap-x-1.5 whitespace-nowrap py-2 text-sm font-semibold uppercase tracking-wide text-zonetec-ink transition-colors hover:text-zonetec-blue"
                    >
                        {{ $link['label'] }}

                        @if (! empty($link['children']))
                            <span class="icon-arrow-down text-lg" role="presentation"></span>
                        @endif
                    </a>

                    @if (! empty($link['children']))
                        <div class="pointer-events-none absolute top-full z-[3] w-max min-w-[200px] translate-y-1 border border-zonetec-border bg-white py-2 opacity-0 shadow-[0_8px_16px_rgba(0,0,0,.14)] transition duration-200 group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 ltr:left-0 rtl:right-0">
                            @foreach ($link['children'] as $child)
                                <a
                                    href="{{ $child['url'] }}"
                                    class="block px-5 py-2 text-sm text-zonetec-body hover:bg-zonetec-surface hover:text-zonetec-blue"
                                >
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-zonetec-category-template"
    >
        <div
            class="group relative flex w-[230px] shrink-0 max-1180:w-[190px]"
            @mouseenter="isOpen = true"
            @mouseleave="isOpen = false"
        >
            <!-- Trigger -->
            <button
                type="button"
                class="flex w-full items-center justify-between bg-zonetec-blue px-5 text-sm font-bold uppercase tracking-wide text-white"
                @click="isOpen = ! isOpen"
                :aria-expanded="isOpen ? 'true' : 'false'"
            >
                @lang('shop::app.components.layouts.header.desktop.bottom.categories')

                <span class="icon-hamburger text-2xl" role="presentation"></span>
            </button>

            {{--
                Flyout. No `overflow` here on purpose: the second-level panels
                fly out to the side (`left-full`), and any `overflow-y` would
                force the cross-axis to clip and hide them. Height is kept sane
                by the compact rows below, not by scrolling this container.
            --}}
            <div
                class="absolute top-full z-[4] w-full border border-zonetec-border bg-white shadow-[0_8px_16px_rgba(0,0,0,.14)] ltr:left-0 rtl:right-0"
                v-show="isOpen && ! isLoading"
            >
                <div
                    class="group/item relative"
                    v-for="category in categories"
                >
                    <a
                        :href="category.url"
                        class="flex items-center justify-between px-4 py-1.5 text-[13px] text-zonetec-ink hover:bg-zonetec-surface hover:text-zonetec-blue"
                    >
                        @{{ category.name }}

                        <span
                            class="icon-arrow-right rtl:icon-arrow-left text-lg"
                            v-if="category.children && category.children.length"
                            role="presentation"
                        ></span>
                    </a>

                    <!-- Second / third level -->
                    <div
                        class="pointer-events-none absolute top-0 z-[5] hidden max-h-[70vh] w-max min-w-[520px] max-w-[760px] overflow-y-auto border border-zonetec-border bg-white p-6 opacity-0 shadow-[0_8px_16px_rgba(0,0,0,.14)] transition-opacity duration-150 group-hover/item:pointer-events-auto group-hover/item:block group-hover/item:opacity-100 ltr:left-full rtl:right-full"
                        v-if="category.children && category.children.length"
                    >
                        <div class="grid grid-cols-3 gap-x-10 gap-y-6">
                            <div
                                class="grid content-start gap-3"
                                v-for="secondLevel in category.children"
                            >
                                <a
                                    :href="secondLevel.url"
                                    class="text-sm font-bold text-zonetec-ink hover:text-zonetec-blue"
                                >
                                    @{{ secondLevel.name }}
                                </a>

                                <ul
                                    class="grid gap-2"
                                    v-if="secondLevel.children && secondLevel.children.length"
                                >
                                    <li
                                        class="text-sm text-zonetec-body hover:text-zonetec-blue"
                                        v-for="thirdLevel in secondLevel.children"
                                    >
                                        <a :href="thirdLevel.url">
                                            @{{ thirdLevel.name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-zonetec-category', {
            template: '#v-zonetec-category-template',

            data() {
                return {
                    isLoading: true,
                    isOpen: false,
                    categories: [],
                };
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                /**
                 * The category tree rarely changes, so reuse the cached copy the
                 * storefront already keeps under the same `categories` key.
                 */
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories');

                        if (stored) {
                            this.categories = JSON.parse(stored);
                            this.isLoading = false;

                            return;
                        }
                    } catch (e) {}

                    this.getCategories();
                },

                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                            localStorage.setItem('categories', JSON.stringify(this.categories));
                        })
                        .catch(error => {
                            this.isLoading = false;
                            console.log(error);
                        });
                },
            },
        });
    </script>
@endPushOnce

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.nav.after') !!}
