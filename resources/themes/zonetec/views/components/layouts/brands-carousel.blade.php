@props([
    /**
     * Section heading. Pass an empty string to render the strip without one.
     */
    'title' => 'Shop by Brand',
])

@php
    /**
     * Brands are options on the catalogue's native `brand` attribute — the same
     * attribute the storefront filters on, so there is no separate brand list
     * to keep in sync. They are managed in Admin → Catalog → Brands, which also
     * carries each one's logo in the option's `swatch_value` column.
     *
     * A brand with no logo uploaded falls back to its name set as a wordmark, so
     * a missing image never leaves a hole in the row.
     */
    $brandAttribute = app(\Webkul\Attribute\Repositories\AttributeRepository::class)
        ->findOneWhere(['code' => 'brand']);

    $brands = $brandAttribute
        ? $brandAttribute->options()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($option) => [
                'id'    => $option->id,
                'label' => $option->label ?: $option->admin_name,
                'logo'  => $option->swatch_value
                    ? \Illuminate\Support\Facades\Storage::url($option->swatch_value)
                    : null,

                /**
                 * The listing's filter layer hydrates itself from the query
                 * string, so `?brand=<option id>` lands on a pre-filtered
                 * search page without any extra route.
                 */
                'url'   => route('shop.search.index', ['brand' => $option->id]),
            ])
            ->values()
            ->all()
        : [];
@endphp

@if (! empty($brands))
    <v-brands-carousel
        :brands="@js($brands)"
        title="{{ $title }}"
    ></v-brands-carousel>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-brands-carousel-template"
        >
            <div class="container mt-14 max-lg:px-8 max-md:mt-8 max-sm:mt-7 max-sm:!px-4">
                <h2
                    class="font-dmserif text-3xl max-md:text-2xl max-sm:text-xl"
                    v-if="title"
                    v-text="title"
                >
                </h2>

                <div class="relative mt-10 max-md:mt-5">
                    {{--
                        With few brands the tiles spread evenly across the row
                        (`justify-evenly`); once the track would overflow they
                        left-align and scroll, driven by the arrows and dots.
                    --}}
                    <div
                        ref="track"
                        class="scrollbar-hide flex snap-x gap-10 overflow-auto scroll-smooth max-lg:gap-6 max-sm:gap-4"
                        :class="isScrollable ? 'justify-start' : 'justify-evenly'"
                        @scroll="syncActivePage"
                    >
                        <a
                            class="grid min-w-[120px] max-w-[120px] snap-start grid-cols-1 justify-items-center gap-4 max-md:min-w-20 max-md:max-w-20 max-md:gap-2.5 max-sm:min-w-[76px] max-sm:max-w-[76px] max-sm:gap-1.5"
                            v-for="brand in brands"
                            :key="brand.id"
                            :href="brand.url"
                            :aria-label="brand.label"
                        >
                            <span class="flex h-[110px] w-[110px] items-center justify-center overflow-hidden rounded-full bg-zonetec-surface transition-shadow hover:shadow-md max-md:h-20 max-md:w-20 max-sm:h-[76px] max-sm:w-[76px]">
                                <img
                                    class="h-full w-full rounded-full object-contain max-md:p-3.5"
                                    v-if="brand.logo"
                                    :src="brand.logo"
                                    :alt="brand.label"
                                    width="110"
                                    height="110"
                                    loading="lazy"
                                    decoding="async"
                                />

                                {{--
                                    Wordmark fallback: without it a logo-less
                                    brand would render as an empty grey disc.
                                --}}
                                <span
                                    class="px-2 text-center text-sm font-bold uppercase leading-tight tracking-tight text-zonetec-ink max-md:text-[11px] max-md:px-1"
                                    v-else
                                    v-text="brand.label"
                                >
                                </span>
                            </span>

                            <p
                                class="text-center text-lg font-bold text-zonetec-ink max-md:text-base max-sm:text-sm"
                                v-text="brand.label"
                            >
                            </p>
                        </a>
                    </div>

                    <span
                        class="icon-arrow-left-stylish rtl:icon-arrow-right-stylish absolute -left-10 top-[55px] flex h-[50px] w-[50px] -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-zonetec-border bg-white text-2xl transition hover:bg-zonetec-ink hover:text-white max-lg:-left-7 max-md:hidden"
                        role="button"
                        aria-label="@lang('shop::components.carousel.previous')"
                        tabindex="0"
                        v-if="isScrollable"
                        @click="swipe(-1)"
                    >
                    </span>

                    <span
                        class="icon-arrow-right-stylish rtl:icon-arrow-left-stylish absolute -right-10 top-[55px] flex h-[50px] w-[50px] -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-zonetec-border bg-white text-2xl transition hover:bg-zonetec-ink hover:text-white max-lg:-right-7 max-md:hidden"
                        role="button"
                        aria-label="@lang('shop::components.carousel.next')"
                        tabindex="0"
                        v-if="isScrollable"
                        @click="swipe(1)"
                    >
                    </span>
                </div>

                <!-- Page indicator -->
                <div
                    class="mt-6 flex items-center justify-center gap-2 max-md:mt-4"
                    v-if="pageCount > 1"
                >
                    <button
                        class="h-1.5 rounded-full transition-all"
                        v-for="page in pageCount"
                        :key="page"
                        :class="page - 1 === activePage ? 'w-6 bg-zonetec-ink' : 'w-1.5 bg-zonetec-border'"
                        :aria-label="'Brands page ' + page"
                        :aria-current="page - 1 === activePage"
                        @click="scrollToPage(page - 1)"
                    >
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-brands-carousel', {
                template: '#v-brands-carousel-template',

                props: [
                    'brands',
                    'title',
                ],

                data() {
                    return {
                        /**
                         * Whether the track overflows its container. When false
                         * the tiles are spread evenly and neither the arrows nor
                         * the dots are shown.
                         */
                        isScrollable: false,

                        /**
                         * The track scrolls a viewport at a time, so a "page" is
                         * one container width of tiles.
                         */
                        pageCount: 0,

                        activePage: 0,

                        /**
                         * In RTL the track's `scrollLeft` runs negative, so the
                         * scroll maths is mirrored.
                         */
                        isRtl: false,
                    };
                },

                mounted() {
                    this.isRtl = getComputedStyle(this.$refs.track).direction === 'rtl';

                    this.measure();

                    window.addEventListener('resize', this.measure);
                },

                beforeUnmount() {
                    window.removeEventListener('resize', this.measure);
                },

                methods: {
                    measure() {
                        const track = this.$refs.track;

                        if (! track) {
                            return;
                        }

                        // +1 guards against sub-pixel rounding.
                        this.isScrollable = track.scrollWidth > track.clientWidth + 1;

                        this.pageCount = this.isScrollable
                            ? Math.ceil(track.scrollWidth / track.clientWidth)
                            : 0;

                        this.syncActivePage();
                    },

                    syncActivePage() {
                        const track = this.$refs.track;

                        if (! track?.clientWidth) {
                            return;
                        }

                        this.activePage = Math.min(
                            Math.round(Math.abs(track.scrollLeft) / track.clientWidth),
                            Math.max(this.pageCount - 1, 0)
                        );
                    },

                    scrollToPage(page) {
                        const track = this.$refs.track;

                        track.scrollLeft = page * track.clientWidth * (this.isRtl ? -1 : 1);
                    },

                    /**
                     * `direction` is 1 for the next page, -1 for the previous;
                     * clamped at both ends rather than wrapping so it stays in
                     * step with the page indicator.
                     */
                    swipe(direction) {
                        this.scrollToPage(
                            Math.min(Math.max(this.activePage + direction, 0), this.pageCount - 1)
                        );
                    },
                },
            });
        </script>
    @endPushOnce
@endif
