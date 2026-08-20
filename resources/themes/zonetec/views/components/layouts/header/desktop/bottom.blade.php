{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

<div class="flex min-h-[92px] w-full items-center gap-x-12 bg-white px-[60px] max-1180:gap-x-8 max-1180:px-8">
    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

    <a
        href="{{ route('shop.home.index') }}"
        class="shrink-0"
        aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
    >
        <img
            src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
            class="h-16 w-auto object-contain max-1180:h-14"
            alt="{{ config('app.name') }}"
        >
    </a>

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

    {{--
        Search field with an attached solid submit button, rather than the
        default theme's icon-inside-a-pill treatment.

        The plain form below is the pre-mount / no-JS fallback: `v-search-bar`
        replaces it with the same markup plus a suggestions dropdown, so search
        keeps working if the bundle fails to load.
    --}}
    <div class="relative grow">
        <v-search-bar
            action="{{ route('shop.search.index') }}"
            src="{{ route('shop.api.products.index') }}"
            product-url-template="{{ route('shop.product_or_category.index', ':slug') }}"
            initial-query="{{ request('query') }}"
            min-length="{{ core()->getConfigData('catalog.products.search.min_query_length') ?: 3 }}"
            max-length="{{ core()->getConfigData('catalog.products.search.max_query_length') ?: 100 }}"
        >
            <form
                action="{{ route('shop.search.index') }}"
                class="flex items-stretch"
                role="search"
            >
                <label
                    for="organic-search"
                    class="sr-only"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.search')
                </label>

                <input
                    type="text"
                    name="query"
                    value="{{ request('query') }}"
                    class="block w-full border border-zonetec-ink/25 px-4 py-3 text-sm text-zonetec-ink outline-none transition-colors placeholder:text-zonetec-muted focus:border-zonetec-blue ltr:border-r-0 ltr:rounded-l-sm rtl:border-l-0 rtl:rounded-r-sm"
                    minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                    maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                    placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-required="true"
                    pattern="[^\\]+"
                    required
                >

                <button
                    type="submit"
                    class="flex w-[62px] shrink-0 items-center justify-center bg-zonetec-blue text-white transition-colors hover:bg-zonetec-blueDark ltr:rounded-r-sm rtl:rounded-l-sm"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.submit')"
                >
                    <span class="icon-search text-2xl" role="presentation"></span>
                </button>
            </form>
        </v-search-bar>
    </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}

    <!-- Right Navigation Links -->
    <div class="flex shrink-0 items-center gap-x-7 max-1180:gap-x-5">

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

        <!-- Compare -->
        @if (core()->getConfigData('catalog.products.settings.compare_option'))
            <a
                href="{{ route('shop.compare.index') }}"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.compare')"
            >
                <span
                    class="icon-compare inline-block cursor-pointer text-[28px] text-zonetec-ink hover:text-zonetec-blue"
                    role="presentation"
                ></span>
            </a>
        @endif

        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
            <a
                href="{{ route('shop.customers.account.wishlist.index') }}"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.wishlist')"
            >
                <span
                    class="icon-heart inline-block cursor-pointer text-[28px] text-zonetec-ink hover:text-zonetec-blue"
                    role="presentation"
                ></span>
            </a>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

        <!-- Mini cart -->
        @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
            @include('shop::checkout.cart.mini-cart')
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

        {{-- Account: sign in / sign up for guests; profile dropdown for customers --}}
        @guest('customer')
            <span class="flex items-center gap-x-1.5 whitespace-nowrap text-sm font-semibold text-zonetec-ink">
                <a
                    href="{{ route('shop.customer.session.create') }}"
                    class="hover:text-zonetec-blue"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                </a>

                <span class="text-zonetec-muted">/</span>

                <a
                    href="{{ route('shop.customers.register.index') }}"
                    class="hover:text-zonetec-blue"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.sign-up')
                </a>
            </span>
        @endguest

        @auth('customer')
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <span
                        class="icon-users inline-block cursor-pointer text-[28px] text-zonetec-ink hover:text-zonetec-blue"
                        role="button"
                        aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                        tabindex="0"
                    ></span>
                </x-slot>

                <x-slot:content class="!p-0">
                    <div class="grid gap-2.5 p-5 pb-0">
                        <p class="text-base font-bold text-zonetec-ink" v-pre>
                            @lang('shop::app.components.layouts.header.desktop.bottom.welcome')
                            {{ auth()->guard('customer')->user()->first_name }}
                        </p>
                    </div>

                    <p class="mt-3 w-full border border-zinc-200"></p>

                    <div class="mt-2.5 grid gap-1 pb-2.5">
                        <a
                            class="cursor-pointer px-5 py-2 text-base hover:bg-zonetec-surface hover:text-zonetec-blue"
                            href="{{ route('shop.customers.account.profile.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                        </a>

                        <!-- Customer logout -->
                        <x-shop::form
                            method="DELETE"
                            action="{{ route('shop.customer.session.destroy') }}"
                            id="customerLogout"
                        />

                        <a
                            class="cursor-pointer px-5 py-2 text-base hover:bg-zonetec-surface hover:text-zonetec-blue"
                            href="{{ route('shop.customer.session.destroy') }}"
                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                        </a>
                    </div>
                </x-slot>
            </x-shop::dropdown>
        @endauth

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
    </div>
</div>

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-search-bar-template"
    >
        <div class="relative">
            <form
                :action="action"
                class="flex items-stretch"
                role="search"
                toolname="search_products"
                tooldescription="{{ trans('shop::app.components.layouts.webmcp.search-products') }}"
                toolautosubmit
                @submit="onSubmit"
            >
                <label
                    for="organic-search"
                    class="sr-only"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.search')
                </label>

                <input
                    ref="input"
                    id="organic-search"
                    type="text"
                    name="query"
                    v-model="query"
                    toolparamdescription="{{ trans('shop::app.components.layouts.webmcp.search-products-query') }}"
                    class="block w-full border border-zonetec-ink/25 px-4 py-3 text-sm text-zonetec-ink outline-none transition-colors placeholder:text-zonetec-muted focus:border-zonetec-blue ltr:border-r-0 ltr:rounded-l-sm rtl:border-l-0 rtl:rounded-r-sm"
                    :minlength="minLength"
                    :maxlength="maxLength"
                    placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-required="true"
                    pattern="[^\\]+"
                    autocomplete="off"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="search-suggestions"
                    :aria-expanded="isOpen ? 'true' : 'false'"
                    :aria-activedescendant="highlighted >= 0 ? 'search-suggestion-' + highlighted : null"
                    required
                    @input="onInput"
                    @focus="onFocus"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter="onEnter"
                    @keydown.esc="close"
                >

                <button
                    type="submit"
                    class="flex w-[62px] shrink-0 items-center justify-center bg-zonetec-blue text-white transition-colors hover:bg-zonetec-blueDark ltr:rounded-r-sm rtl:rounded-l-sm"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.submit')"
                >
                    <span class="icon-search text-2xl" role="presentation"></span>
                </button>
            </form>

            <!-- Suggestions -->
            <div
                id="search-suggestions"
                role="listbox"
                class="absolute inset-x-0 top-full z-30 mt-1 overflow-hidden rounded-sm border border-zonetec-border bg-white shadow-[0_8px_24px_rgba(0,0,0,0.12)]"
                v-if="isOpen"
            >
                <!-- Loading -->
                <div
                    class="grid gap-3 p-3"
                    v-if="isLoading"
                >
                    <div
                        class="flex animate-pulse items-center gap-3"
                        v-for="n in 3"
                        :key="n"
                    >
                        <div class="h-12 w-12 shrink-0 rounded-sm bg-zonetec-surface"></div>

                        <div class="grid w-full gap-1.5">
                            <div class="h-3 w-3/5 rounded-sm bg-zonetec-surface"></div>

                            <div class="h-3 w-1/4 rounded-sm bg-zonetec-surface"></div>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- Results -->
                    <a
                        v-for="(product, index) in products"
                        :key="product.id"
                        :id="'search-suggestion-' + index"
                        :href="productUrl(product)"
                        role="option"
                        :aria-selected="highlighted === index ? 'true' : 'false'"
                        class="flex items-center gap-3 px-3 py-2.5 transition-colors"
                        :class="highlighted === index ? 'bg-zonetec-surface' : 'bg-white'"
                        @mouseenter="highlighted = index"
                        @mousedown.prevent="visit(product)"
                    >
                        <img
                            :src="product.base_image.small_image_url"
                            :alt="product.name"
                            class="h-12 w-12 shrink-0 rounded-sm border border-zonetec-border bg-white object-contain"
                            width="48"
                            height="48"
                            loading="lazy"
                        >

                        <span class="grid gap-0.5 overflow-hidden">
                            <span class="truncate text-sm text-zonetec-ink">@{{ product.name }}</span>

                            <span class="text-sm font-bold text-zonetec-blue">@{{ product.min_price }}</span>
                        </span>
                    </a>

                    <!-- Empty state -->
                    <p
                        class="px-3 py-6 text-center text-sm text-zonetec-muted"
                        v-if="! products.length"
                    >
                        @lang('shop::app.categories.view.empty')
                    </p>

                    <!-- View all -->
                    <a
                        :href="action + '?query=' + encodeURIComponent(query)"
                        class="block border-t border-zonetec-border bg-zonetec-surface px-3 py-3 text-center text-sm font-bold text-zonetec-blue hover:bg-zonetec-border"
                        v-if="products.length"
                    >
                        @lang('shop::app.components.products.carousel.view-all')
                    </a>
                </template>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-search-bar', {
            template: '#v-search-bar-template',

            props: [
                'action',
                'src',
                'productUrlTemplate',
                'initialQuery',
                'minLength',
                'maxLength',
            ],

            data() {
                return {
                    query: this.initialQuery ?? '',

                    products: [],

                    isLoading: false,

                    isOpen: false,

                    /**
                     * Index of the keyboard-highlighted row; -1 means the input
                     * itself holds focus and Enter should submit the form.
                     */
                    highlighted: -1,

                    /**
                     * Debounce handle, and a monotonic request id so a slow
                     * response for an earlier keystroke cannot overwrite the
                     * results of a later one.
                     */
                    timer: null,

                    requestId: 0,
                };
            },

            computed: {
                threshold() {
                    return Number(this.minLength) || 3;
                },
            },

            mounted() {
                document.addEventListener('click', this.onDocumentClick);
            },

            unmounted() {
                document.removeEventListener('click', this.onDocumentClick);

                clearTimeout(this.timer);
            },

            methods: {
                onInput() {
                    clearTimeout(this.timer);

                    this.highlighted = -1;

                    if (this.query.trim().length < this.threshold) {
                        this.close();

                        this.products = [];

                        return;
                    }

                    this.isLoading = true;

                    this.isOpen = true;

                    this.timer = setTimeout(this.fetch, 300);
                },

                fetch() {
                    const requestId = ++this.requestId;

                    this.$axios.get(this.src, {
                            params: {
                                query: this.query.trim(),
                                limit: 6,
                            },
                        })
                        .then(response => {
                            // A newer keystroke already fired; discard this one.
                            if (requestId !== this.requestId) {
                                return;
                            }

                            this.products = response.data.data ?? [];

                            this.isLoading = false;
                        })
                        .catch(() => {
                            if (requestId !== this.requestId) {
                                return;
                            }

                            this.products = [];

                            this.isLoading = false;

                            this.close();
                        });
                },

                onFocus() {
                    if (this.query.trim().length >= this.threshold && this.products.length) {
                        this.isOpen = true;
                    }
                },

                /**
                 * Arrow keys walk the list and wrap around at both ends.
                 */
                move(step) {
                    if (! this.isOpen || ! this.products.length) {
                        return;
                    }

                    const count = this.products.length;

                    this.highlighted = (this.highlighted + step + count) % count;
                },

                /**
                 * Enter opens the highlighted suggestion; with nothing
                 * highlighted it falls through to the form's normal submit.
                 */
                onEnter(event) {
                    const product = this.products[this.highlighted];

                    if (this.isOpen && product) {
                        event.preventDefault();

                        this.visit(product);
                    }
                },

                onSubmit() {
                    this.close();
                },

                onDocumentClick(event) {
                    if (! this.$el.contains(event.target)) {
                        this.close();
                    }
                },

                close() {
                    this.isOpen = false;

                    this.highlighted = -1;
                },

                productUrl(product) {
                    return this.productUrlTemplate.replace(':slug', product.url_key);
                },

                visit(product) {
                    window.location.href = this.productUrl(product);
                },
            },
        });
    </script>
@endPushOnce
