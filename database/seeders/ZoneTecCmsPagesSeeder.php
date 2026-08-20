<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Replaces Bagisto's placeholder CMS page copy with production storefront content
 * for the ZoneTec store.
 *
 * Idempotent: pages are matched by `url_key`, so re-running refreshes the copy
 * without creating duplicates. Pages missing from `cms_pages` are created and
 * linked to every channel.
 *
 * Markup constraint: the storefront renders `html_content` raw, but the admin
 * panel pushes it through HTMLPurifier (`clean_content()`) on save. Everything
 * here therefore stays inside `config/purify.php`'s allowed tags and
 * `CSS.AllowedProperties` — inline styles only, no `<style>` blocks, no Tailwind
 * classes (the theme's Vite build only scans Blade files, so DB-side classes get
 * purged), and no `line-height`/`border-radius`/`display`/`max-width`. A merchant
 * can open any of these pages in the admin editor and save without losing layout.
 *
 * Placeholders in SQUARE BRACKETS are deliberate — they are the real-world
 * details the store owner must fill in (see the class constants below). Search
 * the storefront for "[" after seeding to find every one.
 */
class ZoneTecCmsPagesSeeder extends Seeder
{
    /**
     * Shown in the "last updated" line on every policy page.
     */
    const REVISION_DATE = '30 July 2026';

    /**
     * Merchant-supplied details. Swap these for real values and re-run the
     * seeder to update all pages at once.
     */
    const COMPANY = '[COMPANY LEGAL NAME]';

    const EMAIL = '[SUPPORT EMAIL]';

    const PHONE = '[SUPPORT PHONE]';

    const WHATSAPP = '[WHATSAPP NUMBER]';

    const ADDRESS = '[STORE ADDRESS]';

    const REGISTER = '[COMMERCIAL REGISTER NO.]';

    /**
     * Brand tokens mirrored from the theme's tailwind config.
     */
    const INK = '#282828';

    const BODY = '#505050';

    const MUTED = '#a5a5a5';

    const BLUE = '#1754c3';

    const SURFACE = '#f2f2f2';

    const BORDER = '#e8e8e8';

    /**
     * Run the seeder.
     */
    public function run(): void
    {
        $locales = DB::table('locales')->pluck('code')->all() ?: [config('app.locale')];

        $channelIds = DB::table('channels')->pluck('id')->all();

        foreach ($this->pages() as $urlKey => $page) {
            $pageId = $this->resolvePageId($urlKey, $channelIds);

            foreach ($locales as $locale) {
                $this->upsertTranslation($pageId, $locale, $urlKey, $page);
            }

            $this->command?->info("Seeded CMS page: {$urlKey}");
        }
    }

    /**
     * Find the page that already owns this url_key, or create a fresh one and
     * attach it to every channel.
     */
    protected function resolvePageId(string $urlKey, array $channelIds): int
    {
        $pageId = DB::table('cms_page_translations')
            ->where('url_key', $urlKey)
            ->value('cms_page_id');

        if ($pageId) {
            return (int) $pageId;
        }

        $pageId = DB::table('cms_pages')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        foreach ($channelIds as $channelId) {
            DB::table('cms_page_channels')->insert([
                'cms_page_id' => $pageId,
                'channel_id' => $channelId,
            ]);
        }

        return $pageId;
    }

    /**
     * Write (or overwrite) one locale's copy for a page.
     */
    protected function upsertTranslation(int $pageId, string $locale, string $urlKey, array $page): void
    {
        $payload = [
            'url_key' => $urlKey,
            'page_title' => $page['title'],
            'html_content' => $page['content'],
            'meta_title' => $page['meta_title'],
            'meta_description' => $page['meta_description'],
            'meta_keywords' => $page['meta_keywords'],
        ];

        $exists = DB::table('cms_page_translations')
            ->where('cms_page_id', $pageId)
            ->where('locale', $locale)
            ->exists();

        if ($exists) {
            DB::table('cms_page_translations')
                ->where('cms_page_id', $pageId)
                ->where('locale', $locale)
                ->update($payload);

            return;
        }

        DB::table('cms_page_translations')->insert($payload + [
            'cms_page_id' => $pageId,
            'locale' => $locale,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Markup helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Page shell: heading, standfirst, revision line, then the body sections.
     */
    protected function page(string $heading, string $lede, array $sections, bool $dated = true): string
    {
        $html = '<div class="static-container" style="padding-bottom:48px;color:'.self::BODY.';font-size:15px;">';

        $html .= '<h1 style="font-size:34px;font-weight:600;color:'.self::INK.';margin-bottom:12px;">'.$heading.'</h1>';

        $html .= '<p style="font-size:17px;color:'.self::BODY.';margin-bottom:16px;">'.$lede.'</p>';

        if ($dated) {
            $html .= '<p style="font-size:13px;color:'.self::MUTED.';margin-bottom:8px;">Last updated: '.self::REVISION_DATE.'</p>';
        }

        $html .= '<hr>';

        $html .= implode('', $sections);

        return $html.'</div>';
    }

    /**
     * A titled section: rule-underlined h2 followed by its blocks.
     */
    protected function section(string $title, array $blocks): string
    {
        $html = '<h2 style="font-size:21px;font-weight:600;color:'.self::INK.';margin-top:34px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid '.self::BORDER.';">'.$title.'</h2>';

        return $html.implode('', $blocks);
    }

    /**
     * Sub-heading inside a section.
     */
    protected function h3(string $text): string
    {
        return '<h3 style="font-size:16px;font-weight:600;color:'.self::INK.';margin-top:22px;margin-bottom:8px;">'.$text.'</h3>';
    }

    /**
     * Body paragraph.
     */
    protected function p(string $text): string
    {
        return '<p style="font-size:15px;color:'.self::BODY.';margin-bottom:14px;">'.$text.'</p>';
    }

    /**
     * Bulleted list.
     */
    protected function ul(array $items): string
    {
        $html = '<ul style="margin-top:0;margin-bottom:18px;padding-left:24px;">';

        foreach ($items as $item) {
            $html .= '<li style="font-size:15px;color:'.self::BODY.';margin-bottom:8px;">'.$item.'</li>';
        }

        return $html.'</ul>';
    }

    /**
     * Numbered list.
     */
    protected function ol(array $items): string
    {
        $html = '<ol style="margin-top:0;margin-bottom:18px;padding-left:24px;">';

        foreach ($items as $item) {
            $html .= '<li style="font-size:15px;color:'.self::BODY.';margin-bottom:8px;">'.$item.'</li>';
        }

        return $html.'</ol>';
    }

    /**
     * Highlighted note with a brand-blue rule down the side.
     */
    protected function callout(string $text): string
    {
        return '<div style="background-color:'.self::SURFACE.';border-left:4px solid '.self::BLUE.';padding-top:16px;padding-bottom:16px;padding-left:18px;padding-right:18px;margin-top:18px;margin-bottom:22px;">'
            .'<p style="font-size:15px;color:'.self::INK.';margin-bottom:0;">'.$text.'</p>'
            .'</div>';
    }

    /**
     * Data table. $rows is a list of cell arrays matching $headers.
     */
    protected function table(array $headers, array $rows): string
    {
        $th = 'style="font-size:14px;font-weight:600;color:'.self::INK.';background-color:'.self::SURFACE.';border:1px solid '.self::BORDER.';padding-top:10px;padding-bottom:10px;padding-left:14px;padding-right:14px;text-align:left;vertical-align:top;"';

        $td = 'style="font-size:14px;color:'.self::BODY.';border:1px solid '.self::BORDER.';padding-top:10px;padding-bottom:10px;padding-left:14px;padding-right:14px;text-align:left;vertical-align:top;"';

        $html = '<table style="width:100%;border-collapse:collapse;margin-top:6px;margin-bottom:22px;"><thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th scope="col" '.$th.'>'.$header.'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach ($row as $cell) {
                $html .= '<td '.$td.'>'.$cell.'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    /**
     * Storefront link.
     */
    protected function link(string $path, string $label): string
    {
        return '<a href="'.$path.'" style="color:'.self::BLUE.';text-decoration:underline;">'.$label.'</a>';
    }

    /**
     * The standard "still need help?" block that closes every policy page.
     */
    protected function contactSection(string $intro): string
    {
        return $this->section('Contact Us', [
            $this->p($intro),
            $this->ul([
                '<strong>Email:</strong> '.self::EMAIL,
                '<strong>Phone:</strong> '.self::PHONE,
                '<strong>WhatsApp:</strong> '.self::WHATSAPP,
                '<strong>Showroom:</strong> '.self::ADDRESS,
                '<strong>Contact form:</strong> '.$this->link('/contact-us', 'send us a message'),
            ]),
            $this->p('Our support hours are Monday to Friday, 9:00 to 18:00, and Saturday, 10:00 to 16:00 (Beirut time). We are closed on Sundays and public holidays.'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Page content
    |--------------------------------------------------------------------------
    */

    /**
     * All seeded pages, keyed by url_key.
     */
    protected function pages(): array
    {
        return [
            'about-us' => $this->aboutUs(),
            'customer-service' => $this->customerService(),
            'whats-new' => $this->whatsNew(),
            'shipping-policy' => $this->shippingPolicy(),
            'payment-policy' => $this->paymentPolicy(),
            'return-policy' => $this->returnPolicy(),
            'refund-policy' => $this->refundPolicy(),
            'terms-conditions' => $this->termsConditions(),
            'privacy-policy' => $this->privacyPolicy(),
        ];
    }

    protected function aboutUs(): array
    {
        return [
            'title' => 'About Us',
            'meta_title' => 'About ZoneTec — Computers, Components & IT Solutions',
            'meta_description' => 'ZoneTec is a specialist computer and electronics retailer supplying laptops, desktops, components, networking and gaming hardware to home users and businesses.',
            'meta_keywords' => 'about zonetec, computer store, IT hardware supplier, laptops, pc components',
            'content' => $this->page(
                'About ZoneTec',
                'We are a specialist computer and electronics retailer. We sell the hardware we would use ourselves, we explain it in plain language, and we stand behind it after the sale.',
                [
                    $this->section('Who We Are', [
                        $this->p('ZoneTec was built around a simple observation: buying technology is easy, but buying the <em>right</em> technology is not. Specifications are written for engineers, marketing claims rarely match real-world performance, and most shoppers are left comparing numbers they were never given the context to interpret.'),
                        $this->p('We exist to close that gap. Our catalogue is curated rather than exhaustive, our product pages carry the specifications that actually affect your experience, and our team is made up of people who build, repair and deploy this equipment every day. Whether you are a student choosing a first laptop, a creator specifying a workstation, or an IT manager refreshing a fleet, you deal with someone who understands the decision in front of you.'),
                    ]),

                    $this->section('What We Sell', [
                        $this->p('Our range covers the full computing stack, from complete systems through to the components and accessories that keep them running.'),
                        $this->ul([
                            '<strong>Laptops &amp; notebooks</strong> — ultraportables, business machines, creator laptops and gaming notebooks.',
                            '<strong>Desktops &amp; workstations</strong> — pre-built systems, all-in-ones, small-form-factor PCs and custom builds.',
                            '<strong>PC components</strong> — processors, motherboards, graphics cards, memory, power supplies, cases and cooling.',
                            '<strong>Storage</strong> — NVMe and SATA solid-state drives, hard disks, external drives and NAS enclosures.',
                            '<strong>Monitors &amp; displays</strong> — productivity, colour-accurate and high-refresh gaming panels, plus mounts and arms.',
                            '<strong>Peripherals</strong> — keyboards, mice, headsets, webcams, microphones, docks and hubs.',
                            '<strong>Networking</strong> — routers, switches, access points, mesh systems and structured cabling.',
                            '<strong>Printers &amp; consumables</strong> — laser and inkjet printers, scanners, toner and ink.',
                            '<strong>Power protection</strong> — UPS units, surge protection and power distribution.',
                            '<strong>Gaming</strong> — controllers, racing and flight peripherals, streaming gear and chairs.',
                        ]),
                    ]),

                    $this->section('Why Customers Buy From Us', [
                        $this->h3('Genuine products, properly sourced'),
                        $this->p('Every item we list is sourced through authorised distribution channels. We do not sell grey-market, refurbished-as-new, or repackaged stock. If a product is open-box, ex-display or refurbished, the listing says so explicitly and the price reflects it.'),

                        $this->h3('Advice before the sale'),
                        $this->p('Ask us what you actually need. Tell us your budget, your workload and your constraints, and we will tell you where the money is best spent — including when the cheaper option is the correct one. We would rather sell you the right product once than the wrong product twice.'),

                        $this->h3('Support after the sale'),
                        $this->p('Warranty claims, driver problems, setup questions and compatibility issues are handled by our own team, not passed to a queue. See our '.$this->link('/page/customer-service', 'Customer Service').' page for how to reach us and what to expect.'),

                        $this->h3('Transparent pricing'),
                        $this->p('The price you see is the price of the product. Shipping is calculated and displayed before you commit, and any applicable taxes are shown at checkout. No fees appear after the fact.'),
                    ]),

                    $this->section('Services', [
                        $this->p('Hardware is only part of the job. We also offer:'),
                        $this->ul([
                            '<strong>Custom PC builds</strong> — specified with you, assembled, cable-managed, stress-tested and delivered ready to use.',
                            '<strong>Upgrades &amp; migration</strong> — memory, storage and GPU upgrades, including cloning your existing installation across.',
                            '<strong>Corporate &amp; bulk supply</strong> — volume quotations, standardised device configurations, staged rollouts and consolidated invoicing.',
                            '<strong>Education &amp; institutional pricing</strong> — dedicated pricing for schools, universities, labs and NGOs.',
                            '<strong>Network design &amp; installation</strong> — surveying, specification and deployment for offices and multi-floor premises.',
                        ]),
                        $this->callout('Buying for a business, a school or a project? Email '.self::EMAIL.' with your requirements and quantities, and our commercial desk will return a formal quotation.'),
                    ]),

                    $this->section('How We Work', [
                        $this->ol([
                            '<strong>We curate.</strong> Products earn a place in the catalogue on reliability, warranty support and value — not on margin alone.',
                            '<strong>We check.</strong> Systems and custom builds are powered on, updated and tested before they leave us.',
                            '<strong>We pack properly.</strong> Components ship in anti-static packaging inside rigid outer cartons. Fragile goods are double-boxed.',
                            '<strong>We stay reachable.</strong> One team handles pre-sales, orders and after-sales, so nothing is lost in a hand-off.',
                        ]),
                    ]),

                    $this->section('Company Details', [
                        $this->ul([
                            '<strong>Registered name:</strong> '.self::COMPANY,
                            '<strong>Commercial register:</strong> '.self::REGISTER,
                            '<strong>Registered address:</strong> '.self::ADDRESS,
                            '<strong>Email:</strong> '.self::EMAIL,
                            '<strong>Phone:</strong> '.self::PHONE,
                        ]),
                    ]),

                    $this->section('Come and Talk to Us', [
                        $this->p('Our showroom is open for browsing, collection and advice. If you would rather buy in person after seeing a machine, that is welcome — the same pricing and warranty applies.'),
                        $this->ul([
                            '<strong>Address:</strong> '.self::ADDRESS,
                            '<strong>Opening hours:</strong> Monday to Friday 9:00–18:00, Saturday 10:00–16:00. Closed Sunday.',
                            '<strong>Get in touch:</strong> '.$this->link('/contact-us', 'contact form').', or call '.self::PHONE.'.',
                        ]),
                    ]),
                ],
                false
            ),
        ];
    }

    protected function customerService(): array
    {
        return [
            'title' => 'Customer Service',
            'meta_title' => 'Customer Service & Support — ZoneTec',
            'meta_description' => 'How to contact ZoneTec support, expected response times, order help, warranty claims and technical assistance for your hardware.',
            'meta_keywords' => 'customer service, support, help, warranty claim, order help, technical support',
            'content' => $this->page(
                'Customer Service',
                'One team handles pre-sales questions, live orders and after-sales support. Here is how to reach us, what to have ready, and how quickly you can expect an answer.',
                [
                    $this->section('How to Reach Us', [
                        $this->table(
                            ['Channel', 'Details', 'Best for', 'Typical response'],
                            [
                                ['Email', self::EMAIL, 'Order queries, warranty claims, quotations, anything needing a record', 'Within 1 business day'],
                                ['Phone', self::PHONE, 'Urgent order issues, stock checks, delivery coordination', 'Immediate during support hours'],
                                ['WhatsApp', self::WHATSAPP, 'Quick questions, photos of a fault, delivery updates', 'Within a few hours'],
                                ['Contact form', $this->link('/contact-us', 'Contact Us'), 'General enquiries when you would rather not email', 'Within 1 business day'],
                                ['Showroom', self::ADDRESS, 'Hands-on advice, collection, drop-off for service', 'Walk in during opening hours'],
                            ]
                        ),
                        $this->p('<strong>Support hours:</strong> Monday to Friday 9:00–18:00 and Saturday 10:00–16:00 (Beirut time). We are closed on Sundays and public holidays. Messages received outside these hours are answered on the next working day.'),
                    ]),

                    $this->section('Before You Contact Us', [
                        $this->p('You will get a faster, more useful answer if you include the following where relevant:'),
                        $this->ul([
                            'Your <strong>order number</strong> (shown in your confirmation email and under '.$this->link('/customer/account/orders', 'My Account &rarr; Orders').').',
                            'The <strong>product name</strong> and, for warranty cases, the <strong>serial number</strong>.',
                            'A clear description of the problem, including exactly when it happens and any on-screen error text.',
                            '<strong>Photos or a short video</strong> of the fault, physical damage, or packaging condition.',
                            'What you have already tried — it saves us both a round of suggestions.',
                        ]),
                    ]),

                    $this->section('Help With Your Order', [
                        $this->h3('Tracking a delivery'),
                        $this->p('Order status is available at any time under '.$this->link('/customer/account/orders', 'My Account &rarr; Orders').'. Once your parcel is handed to the carrier we email a tracking reference. Full details are in our '.$this->link('/page/shipping-policy', 'Shipping Policy').'.'),

                        $this->h3('Changing an order'),
                        $this->p('Contact us as quickly as possible. We can usually amend the delivery address, change a payment method or add items while the order is still being prepared. Once an order has been packed and handed to the carrier it can no longer be changed — you would need to refuse delivery or return the item.'),

                        $this->h3('Cancelling an order'),
                        $this->p('Orders can be cancelled free of charge at any point before dispatch. Email or call us with your order number and we will confirm the cancellation and the refund in writing. Custom-built systems that have already entered assembly may not be cancellable — see our '.$this->link('/page/return-policy', 'Return Policy').'.'),

                        $this->h3('Something is missing, wrong or damaged'),
                        $this->p('Tell us within <strong>48 hours</strong> of delivery and keep all packaging. Send photos of the outer carton, the inner packaging and the item itself. We will arrange a replacement, a redelivery of the missing item, or a refund at no cost to you.'),
                    ]),

                    $this->section('Technical Support', [
                        $this->p('We help with the products we sell. That includes setup, driver and firmware issues, compatibility questions, and diagnosing whether a fault is hardware or software.'),
                        $this->ul([
                            '<strong>Setup and first-use help</strong> — free, by phone, email or WhatsApp.',
                            '<strong>Fault diagnosis</strong> — we will work through it with you remotely before asking you to return anything.',
                            '<strong>Bench service</strong> — bring the unit to the showroom or ship it to us and we will test it directly.',
                            '<strong>Data</strong> — always back up your data before sending any device for service or return. We cannot be responsible for data loss.',
                        ]),
                    ]),

                    $this->section('Warranty Claims', [
                        $this->p('Products carry the manufacturer warranty stated on their product page, and we handle the claim on your behalf rather than sending you to the manufacturer.'),
                        $this->ol([
                            'Email '.self::EMAIL.' with your order number, the product, its serial number and a description of the fault.',
                            'We confirm warranty status and, where needed, run remote diagnostics to rule out a configuration issue.',
                            'We issue a service reference and tell you whether to bring the unit in, ship it to us, or deal with an authorised service centre directly.',
                            'The unit is repaired, replaced or credited according to the manufacturer terms. We keep you updated at each stage.',
                        ]),
                        $this->callout('A fault that appears within the first <strong>7 days</strong> is treated as dead-on-arrival and handled as an immediate replacement, not a warranty repair. See the '.$this->link('/page/return-policy', 'Return Policy').'.'),
                    ]),

                    $this->section('Business &amp; Bulk Enquiries', [
                        $this->p('Our commercial desk handles quotations, tenders, standardised device configurations, staged rollouts and account terms for businesses, schools and institutions. Email '.self::EMAIL.' with your requirements, quantities and target timeline and we will respond with a formal quotation.'),
                    ]),

                    $this->section('If We Get It Wrong', [
                        $this->p('If your issue has not been resolved to your satisfaction, ask for it to be escalated to a supervisor and reference your original ticket or order number. We will review the case and respond with a decision and an explanation within <strong>3 business days</strong>. We would much rather hear about a problem than lose a customer quietly.'),
                    ]),

                    $this->section('Answers You May Not Need to Ask For', [
                        $this->ul([
                            $this->link('/page/shipping-policy', 'Shipping Policy').' — delivery areas, rates, transit times and tracking.',
                            $this->link('/page/payment-policy', 'Payment Policy').' — accepted payment methods, invoicing and cash on delivery.',
                            $this->link('/page/return-policy', 'Return Policy').' — the 14-day return window, conditions and how to start a return.',
                            $this->link('/page/refund-policy', 'Refund Policy').' — how and when refunds are issued.',
                            $this->link('/page/terms-conditions', 'Terms &amp; Conditions').' — the terms governing your purchase.',
                            $this->link('/page/privacy-policy', 'Privacy Policy').' — what data we hold and your rights over it.',
                        ]),
                    ]),
                ]
            ),
        ];
    }

    protected function whatsNew(): array
    {
        return [
            'title' => "What's New",
            'meta_title' => "What's New at ZoneTec — Latest Arrivals & Store Updates",
            'meta_description' => 'New arrivals, expanded categories, new services and current offers at ZoneTec. See what has just landed in our computer and electronics range.',
            'meta_keywords' => 'new arrivals, latest products, new products, store news, offers',
            'content' => $this->page(
                "What's New",
                'New hardware lands with us continuously. This page is where we highlight the arrivals, categories and services worth a second look.',
                [
                    $this->section('Just Arrived', [
                        $this->p('Our newest stock is always listed first in the '.$this->link('/', 'New Arrivals').' section of the homepage. Right now the fastest-moving additions are:'),
                        $this->ul([
                            '<strong>Current-generation laptops</strong> — the latest thin-and-light and creator notebooks, including configurations with on-device AI acceleration.',
                            '<strong>Latest-generation graphics cards</strong> — new GPU tiers across gaming and workstation lines, with matched power supplies and cases in stock.',
                            '<strong>High-capacity NVMe storage</strong> — PCIe 4.0 and 5.0 drives at capacities and prices that make mechanical storage hard to justify for a boot disk.',
                            '<strong>High-refresh and colour-accurate monitors</strong> — new OLED and Mini-LED panels for gaming and production work.',
                            '<strong>Wi-Fi 7 networking</strong> — routers, mesh systems and access points ready for multi-gigabit connections.',
                            '<strong>Docks and hubs</strong> — Thunderbolt and USB4 docking for single-cable desk setups.',
                        ]),
                        $this->callout('Looking for something specific that is not listed yet? Email '.self::EMAIL.' — we can often source to order and will tell you honestly what the lead time looks like.'),
                    ]),

                    $this->section('Categories We Have Expanded', [
                        $this->p('We keep widening the catalogue in the areas customers ask for most:'),
                        $this->ul([
                            '<strong>Workstations and content creation</strong> — deeper coverage of high-core-count CPUs, ECC memory and professional graphics.',
                            '<strong>Home and small-office networking</strong> — a full structured-cabling and access-point range rather than just consumer routers.',
                            '<strong>Power protection</strong> — a broader UPS line-up, sized from a single desktop up to a small rack.',
                            '<strong>Gaming peripherals</strong> — mechanical keyboards with a wider switch selection, plus streaming and capture gear.',
                            '<strong>Storage and backup</strong> — NAS enclosures, drives rated for continuous operation, and external backup solutions.',
                        ]),
                    ]),

                    $this->section('New Services', [
                        $this->h3('Custom PC builds'),
                        $this->p('Specify a machine with us and we assemble, cable-manage, update and stress-test it before it ships. You get a system that works out of the box, with the whole build covered by a single point of support.'),

                        $this->h3('Upgrade and migration service'),
                        $this->p('Bring in an existing machine for a memory, storage or graphics upgrade, and we will clone your current installation across so you do not have to rebuild your environment.'),

                        $this->h3('Business and education accounts'),
                        $this->p('Volume pricing, standardised device configurations, staged rollouts and consolidated invoicing for organisations. Contact '.self::EMAIL.' to open an account.'),
                    ]),

                    $this->section('Current Offers', [
                        $this->p('Live promotions, bundle pricing and clearance stock are always shown on the product pages themselves, with the discount applied automatically at checkout — there is no code to remember. Browse the storefront to see what is currently reduced.'),
                        $this->ul([
                            'Bundle savings when a system is bought with a monitor, peripherals or a UPS.',
                            'End-of-line and open-box units at reduced prices, always clearly labelled as such.',
                            'Seasonal promotions around back-to-school and end-of-year periods.',
                        ]),
                    ]),

                    $this->section('Be First to Know', [
                        $this->p('New stock frequently sells through before we have a chance to feature it. Two ways to get ahead of it:'),
                        $this->ul([
                            '<strong>Subscribe to our newsletter</strong> using the form at the foot of any page — new arrivals and offers, no more than a couple of emails a month.',
                            '<strong>Create an account</strong> at '.$this->link('/customer/register', 'My Account').' to save wishlists, track orders and check out faster.',
                        ]),
                        $this->p('You can unsubscribe at any time from any email we send. See our '.$this->link('/page/privacy-policy', 'Privacy Policy').' for how we handle your details.'),
                    ]),
                ]
            ),
        ];
    }

    protected function shippingPolicy(): array
    {
        return [
            'title' => 'Shipping Policy',
            'meta_title' => 'Shipping Policy — Delivery Times, Rates & Tracking | ZoneTec',
            'meta_description' => 'ZoneTec shipping policy: delivery areas, processing times, shipping rates, tracking, damaged or lost parcels and international orders.',
            'meta_keywords' => 'shipping policy, delivery, shipping rates, tracking, delivery times',
            'content' => $this->page(
                'Shipping Policy',
                'How we prepare, ship and track your order — including delivery timescales, rates and what happens if something goes wrong in transit.',
                [
                    $this->section('1. Where We Ship', [
                        $this->p('We deliver throughout Lebanon, to both residential and commercial addresses. Selected products can also be shipped internationally — where this is available it is offered at checkout once you enter your address.'),
                        $this->p('Some items cannot be shipped to every destination because of size, weight, lithium-battery transport rules, or manufacturer territory restrictions. If a restriction applies to something in your basket, checkout will tell you before you pay.'),
                    ]),

                    $this->section('2. Order Processing Time', [
                        $this->p('Processing time is the period between your payment clearing and your parcel being handed to the carrier. It does not include transit time.'),
                        $this->table(
                            ['Order type', 'Processing time'],
                            [
                                ['In-stock items', '1 business day'],
                                ['Multiple items from different stock locations', '1–2 business days'],
                                ['Custom-built PCs and configured systems', '3–5 business days (assembly and stress testing)'],
                                ['Sourced-to-order items', 'As quoted at the time of order'],
                            ]
                        ),
                        $this->p('Orders placed after 15:00, or on a weekend or public holiday, begin processing on the next business day. During major sale periods processing can take one additional day; we will tell you if your order is affected.'),
                    ]),

                    $this->section('3. Delivery Options and Rates', [
                        $this->p('Shipping is calculated in your basket and shown in full before you confirm payment. You will never be charged a delivery cost you have not already seen.'),
                        $this->table(
                            ['Method', 'Cost', 'Estimated delivery'],
                            [
                                ['Standard delivery (flat rate)', 'US$10.00', '2–4 business days after dispatch'],
                                ['Free delivery', 'US$0.00', '2–5 business days after dispatch. Applied automatically to qualifying orders and promotions, as shown at checkout.'],
                                ['Showroom collection', 'US$0.00', 'Ready once we email you to confirm. Bring your order number and photo ID.'],
                                ['International delivery', 'Quoted at checkout', 'Varies by destination and carrier'],
                            ]
                        ),
                        $this->p('Bulky, heavy or fragile goods — large displays, UPS units, full tower systems — may attract a surcharge or require a specialist carrier. Any such cost is shown at checkout, or confirmed with you in writing before dispatch.'),
                    ]),

                    $this->section('4. Tracking Your Order', [
                        $this->p('Every shipment is trackable.'),
                        $this->ul([
                            'You receive an <strong>order confirmation</strong> email as soon as your order is placed.',
                            'You receive a <strong>dispatch notification</strong> with the carrier name and tracking reference once the parcel leaves us.',
                            'Live status is always visible under '.$this->link('/customer/account/orders', 'My Account &rarr; Orders').'.',
                        ]),
                        $this->p('Tracking references can take up to 24 hours to become active on the carrier network. If yours shows nothing after a full business day, contact us and we will chase it.'),
                    ]),

                    $this->section('5. Delivery Attempts and Failed Delivery', [
                        $this->p('Carriers make up to <strong>two</strong> delivery attempts and will normally telephone before arriving. Please make sure the phone number on your order is one you can be reached on.'),
                        $this->p('If both attempts fail, the parcel is held at the local depot for collection. Undelivered and uncollected parcels are returned to us, and we will contact you to arrange redelivery. Redelivery after a failed attempt caused by an incorrect address, an unreachable recipient or a refused delivery is chargeable at the standard rate.'),
                    ]),

                    $this->section('6. Check Your Parcel on Arrival', [
                        $this->p('Please inspect the outer packaging before you sign for a delivery.'),
                        $this->ol([
                            'If the carton is crushed, punctured, water-damaged or has been opened, note it on the carrier paperwork or refuse the delivery outright.',
                            'Photograph the outer carton, the inner packaging and the product before unpacking any further.',
                            'Report the problem to us within <strong>48 hours</strong> of delivery, with the photos attached.',
                        ]),
                        $this->callout('Transit damage reported within 48 hours with supporting photographs is replaced or refunded at no cost to you. Claims made after 48 hours, or without evidence of the packaging condition, cannot be pursued with the carrier and may be declined.'),
                    ]),

                    $this->section('7. Large, Fragile and Restricted Items', [
                        $this->ul([
                            '<strong>Displays and glass panels</strong> ship double-boxed. Do not accept a monitor whose carton is visibly damaged.',
                            '<strong>UPS units and batteries</strong> are subject to dangerous-goods handling rules, which can limit carrier choice and add a day to transit.',
                            '<strong>Components</strong> ship in anti-static packaging. Please keep it — it is required if you later return the item.',
                            '<strong>Custom builds</strong> travel in a purpose-built retention pack; keep it in case the system ever needs to be returned for service.',
                        ]),
                    ]),

                    $this->section('8. Address Accuracy', [
                        $this->p('You are responsible for the accuracy of the delivery address and contact details you provide. We ship to the address exactly as entered.'),
                        $this->p('Address changes are usually possible while the order is still being prepared — contact us immediately. Once a parcel is with the carrier we cannot guarantee a change, and any charge the carrier applies for a redirection will be passed on. Parcels lost as a result of an incorrect address supplied by you are not our responsibility.'),
                    ]),

                    $this->section('9. Delays Outside Our Control', [
                        $this->p('Delivery estimates are estimates, not guarantees. Carrier backlogs, customs inspections, severe weather, road closures, strikes, public holidays and other events beyond our reasonable control can extend transit times. We will keep you informed and pursue the carrier on your behalf, but we cannot accept liability for consequential losses caused by a late delivery.'),
                    ]),

                    $this->section('10. Split and Partial Shipments', [
                        $this->p('Where an order contains items held in different locations, we may ship it in parts at no extra cost so that available items are not held back. Each shipment is tracked separately and you will receive a dispatch notification for each.'),
                    ]),

                    $this->section('11. International Orders, Duties and Taxes', [
                        $this->p('For deliveries outside Lebanon, the prices shown exclude import duties, destination taxes and customs clearance fees. These are levied by the destination country and are payable by you, normally to the carrier on delivery.'),
                        $this->p('We declare the full commercial value on all customs documentation. We cannot mark a shipment as a gift or under-declare its value. Where a shipment is refused or abandoned at customs, any return freight and charges incurred are deducted from your refund.'),
                    ]),

                    $this->section('12. Lost Shipments', [
                        $this->p('A parcel is treated as lost once the carrier confirms it, or once tracking has shown no movement for <strong>10 business days</strong>. Report a suspected loss as soon as you notice it.'),
                        $this->p('We open a claim with the carrier and, once the loss is confirmed, send a replacement or issue a full refund including the original shipping charge — your choice. Where tracking records a completed delivery, we will request the carrier proof of delivery and share it with you as part of the investigation.'),
                    ]),

                    $this->contactSection('For any question about a delivery, quote your order number and we can look it up immediately:'),
                ]
            ),
        ];
    }

    protected function paymentPolicy(): array
    {
        return [
            'title' => 'Payment Policy',
            'meta_title' => 'Payment Policy — Accepted Methods & Billing | ZoneTec',
            'meta_description' => 'Accepted payment methods at ZoneTec: cards, PayPal, bank transfer and cash on delivery. Currency, invoicing, security and failed-payment handling.',
            'meta_keywords' => 'payment policy, payment methods, cash on delivery, bank transfer, card payment, invoice',
            'content' => $this->page(
                'Payment Policy',
                'Which payment methods we accept, when you are charged, how we protect your payment details, and what happens when a payment does not go through.',
                [
                    $this->section('1. Accepted Payment Methods', [
                        $this->table(
                            ['Method', 'How it works', 'Notes'],
                            [
                                ['Credit &amp; debit card', 'Visa, Mastercard and American Express, processed securely by Stripe.', 'Card details are entered on the processor\'s secure form and are never stored on our servers.'],
                                ['PayPal', 'Pay with your PayPal balance, a linked bank account or a card.', 'You are redirected to PayPal to authorise, then returned to complete the order.'],
                                ['Bank / money transfer', 'Transfer the order total to our account and send us the receipt.', 'The order ships once funds clear. Transfer instructions are shown at checkout and repeated in your confirmation email.'],
                                ['Cash on delivery', 'Pay the courier in cash when the parcel arrives.', 'Available on eligible orders and delivery areas only. Order-value limits apply.'],
                            ]
                        ),
                        $this->p('The methods available to you are shown at checkout and can vary by order value, product type and delivery address. Custom-built systems and sourced-to-order items normally require prepayment.'),
                    ]),

                    $this->section('2. Currency, Pricing and Taxes', [
                        $this->ul([
                            'All prices are displayed and charged in <strong>US dollars (USD)</strong>.',
                            'Applicable taxes are calculated from your delivery address and shown separately in the order summary before you pay.',
                            'Shipping is calculated in the basket and shown before you confirm. Nothing is added afterwards.',
                            'If your bank or card issuer settles in another currency, they set the exchange rate and may add a foreign-transaction fee. That fee is theirs, not ours.',
                        ]),
                    ]),

                    $this->section('3. When You Are Charged', [
                        $this->p('For card payments, your card is authorised at the moment you place the order and captured when the order is confirmed for dispatch. An authorisation is not a charge — if we cancel the order before capture, the authorisation is released by your bank, typically within 3–7 business days.'),
                        $this->p('PayPal payments are taken immediately on authorisation. Bank transfers are complete once the funds arrive in our account. Cash-on-delivery orders are paid at the door.'),
                    ]),

                    $this->section('4. Order Verification and Fraud Screening', [
                        $this->p('All orders pass through automated fraud screening, and high-value or unusual orders receive a manual review. We may contact you to verify your identity, your billing address or your authority to use the payment method before we dispatch.'),
                        $this->p('We reserve the right to cancel and refund in full any order we cannot verify, where the billing and delivery details cannot be reconciled, or where we reasonably suspect fraudulent or unauthorised use of a payment method. A cancellation on these grounds is not an accusation, and we will always refund promptly.'),
                    ]),

                    $this->section('5. Paying by Bank or Money Transfer', [
                        $this->ol([
                            'Select bank transfer at checkout and place the order. Your order is created with the status <em>pending payment</em>.',
                            'Transfer the exact order total using our account details, quoting your <strong>order number</strong> as the transfer reference.',
                            'Email the transfer receipt to '.self::EMAIL.'.',
                            'We confirm receipt and release the order for processing, normally within 1 business day of the funds clearing.',
                        ]),
                        $this->callout('Stock is not reserved indefinitely against an unpaid order. Bank-transfer orders are held for <strong>3 business days</strong>. If no payment arrives, and you have not been in touch, the order is cancelled automatically and the stock released. Any transfer fee charged by the sending or receiving bank is payable by you.'),
                    ]),

                    $this->section('6. Cash on Delivery', [
                        $this->p('Where cash on delivery is offered, the following conditions apply:'),
                        $this->ul([
                            'Available only for eligible delivery areas and up to the order value limit shown at checkout.',
                            'The <strong>exact amount</strong> must be ready. Couriers do not carry change and cannot accept part payment.',
                            'The courier may ask to see photo identification matching the order name.',
                            'Payment is due before the parcel is handed over. You may inspect the outer packaging first, but the parcel cannot be opened and tested before payment.',
                            'Refusing a cash-on-delivery parcel without cause may make future cash-on-delivery orders unavailable to you, and any return freight cost may be charged.',
                        ]),
                    ]),

                    $this->section('7. Declined and Failed Payments', [
                        $this->p('If a payment is declined, no order is created and no funds leave your account. Common causes are an expired card, insufficient funds, a mismatch between the billing address and the one your bank holds, or your bank blocking an online or cross-border transaction.'),
                        $this->p('Check your details and try again, use a different method, or contact your bank — they can usually tell you the reason instantly, while we only receive a generic decline code. If you see a pending amount against a failed order, it is an authorisation hold and your bank will release it; contact us with the order number and we will confirm in writing that nothing was captured.'),
                    ]),

                    $this->section('8. Invoices and Receipts', [
                        $this->p('A confirmation email is sent for every order, and a tax invoice is issued once the order is invoiced for dispatch. Invoices are available to download at any time from '.$this->link('/customer/account/orders', 'My Account &rarr; Orders').'.'),
                        $this->p('Business customers who need a company name, registration number or tax identifier on the invoice should enter these in the billing address, or email them to us with the order number before dispatch. We cannot reissue an invoice with different registration details after it has been raised.'),
                    ]),

                    $this->section('9. Pricing Errors', [
                        $this->p('We take care to price accurately, but errors occasionally occur. Where a product is listed at an obviously incorrect price, we are not obliged to supply it at that price. If we discover an error after you have ordered, we will contact you to confirm whether you wish to proceed at the correct price or cancel, and we will refund you in full if you cancel. No order is dispatched at a corrected price without your agreement.'),
                    ]),

                    $this->section('10. Payment Security', [
                        $this->ul([
                            'The entire store is served over encrypted HTTPS connections.',
                            'Card payments are handled by <strong>PCI-DSS compliant</strong> processors. Card numbers, expiry dates and security codes are submitted directly to them.',
                            'We do <strong>not</strong> store full card numbers or CVV codes on our systems at any time.',
                            'We support 3-D Secure. Your bank may ask you to confirm the payment in its app or by one-time code.',
                            'We will never ask you for a full card number, PIN or CVV by email, telephone or chat. Any such request is not from us — report it to '.self::EMAIL.'.',
                        ]),
                    ]),

                    $this->section('11. Chargebacks and Disputes', [
                        $this->p('If something is wrong with your order, please contact us first. Nearly every dispute is resolved faster directly than through a bank, and we would rather fix the problem than argue about it.'),
                        $this->p('Where a chargeback is raised, we will provide the card issuer with the order record, delivery confirmation and our correspondence with you. Accounts with an unresolved chargeback may be restricted to prepayment while the case is open. Fraudulent chargebacks are pursued as recoverable debts.'),
                    ]),

                    $this->section('12. Related Policies', [
                        $this->ul([
                            $this->link('/page/refund-policy', 'Refund Policy').' — how and when money is returned to you.',
                            $this->link('/page/return-policy', 'Return Policy').' — the 14-day return window and its conditions.',
                            $this->link('/page/shipping-policy', 'Shipping Policy').' — delivery rates and timescales.',
                            $this->link('/page/terms-conditions', 'Terms &amp; Conditions').' — the full terms of sale.',
                        ]),
                    ]),

                    $this->contactSection('Questions about a payment, an invoice or a pending charge:'),
                ]
            ),
        ];
    }

    protected function returnPolicy(): array
    {
        return [
            'title' => 'Return Policy',
            'meta_title' => 'Return Policy — 14-Day Returns & Exchanges | ZoneTec',
            'meta_description' => 'Return items to ZoneTec within 14 days. Conditions, non-returnable goods, dead-on-arrival replacements, return shipping costs and how to start a return.',
            'meta_keywords' => 'return policy, returns, exchange, RMA, dead on arrival, 14 day returns',
            'content' => $this->page(
                'Return Policy',
                'You have 14 days to return most items. This page sets out exactly what can be returned, in what condition, who pays the return shipping, and how to start the process.',
                [
                    $this->section('1. The 14-Day Return Window', [
                        $this->p('You may return most items within <strong>14 calendar days</strong> of delivery for a refund or exchange. There is <strong>no restocking fee</strong> on items returned in accordance with this policy.'),
                        $this->p('The window runs from the date the parcel is delivered, as recorded by the carrier. A return request must reach us within those 14 days; the item itself must then arrive with us within <strong>7 days</strong> of us issuing your return authorisation.'),
                    ]),

                    $this->section('2. Condition of Returned Items', [
                        $this->p('To be accepted, a returned item must be:'),
                        $this->ul([
                            '<strong>Complete</strong> — including every cable, adapter, mounting bracket, manual, licence card and free gift supplied with it.',
                            '<strong>In its original packaging</strong>, including inner trays, anti-static bags and protective film. Please do not write on, tape over or use the product box as the shipping carton.',
                            '<strong>Undamaged</strong> — free of scratches, marks, liquid damage, bent pins, thermal paste residue and signs of impact.',
                            '<strong>Unmodified</strong> — with all serial-number and warranty labels intact and unbroken.',
                            '<strong>Reset and unlinked</strong> — signed out of any account, removed from any device-management or activation lock, and with your data securely erased.',
                        ]),
                        $this->p('Inspecting and testing an item is fine and expected. Installing it permanently, applying thermal compound, cutting cable ties on a modular power supply, or otherwise putting it beyond resale is not, and will reduce or void the refund.'),
                    ]),

                    $this->section('3. Items That Cannot Be Returned', [
                        $this->p('The following are non-returnable once supplied, unless they are faulty:'),
                        $this->ul([
                            '<strong>Software, digital licences and activation keys</strong> where the key has been revealed, redeemed or activated.',
                            '<strong>Digital downloads and subscription services</strong> once delivered or started.',
                            '<strong>Custom-built and made-to-order systems</strong> configured to your specification.',
                            '<strong>Sourced-to-order items</strong> brought in specially at your request.',
                            '<strong>In-ear headphones and earbuds</strong> once the hygiene seal is broken.',
                            '<strong>Consumables</strong> — toner, ink, thermal paste, cleaning products, batteries — once opened.',
                            '<strong>Cut-to-length cable</strong> and installed structured cabling.',
                            '<strong>Gift cards and vouchers.</strong>',
                            '<strong>Items sold as-is</strong>, clearance or ex-display where the listing states the sale is final.',
                        ]),
                        $this->callout('None of the above limits your rights where a product is faulty, not as described, or not fit for purpose. Statutory consumer rights under applicable Lebanese law apply regardless of this policy.'),
                    ]),

                    $this->section('4. Faulty on Arrival (First 7 Days)', [
                        $this->p('If an item is dead on arrival, damaged in transit, or the wrong product was sent, tell us within <strong>7 days</strong> of delivery and we will treat it as a priority replacement rather than a standard return.'),
                        $this->ul([
                            'We pay all shipping in both directions.',
                            'You choose a replacement of the same item, a suitable alternative, or a full refund.',
                            'No restocking fee, and no requirement that packaging be unopened.',
                            'Please keep all packaging until the case is closed — we may need it for a carrier claim.',
                        ]),
                        $this->p('Report transit damage within <strong>48 hours</strong> with photographs of the outer carton and contents, as set out in our '.$this->link('/page/shipping-policy', 'Shipping Policy').'.'),
                    ]),

                    $this->section('5. Faults After 14 Days: Warranty', [
                        $this->p('Once the return window has closed, a faulty product is handled under its manufacturer warranty. The warranty period for each product is stated on its product page.'),
                        $this->p('We manage warranty claims for you rather than sending you to the manufacturer. The outcome — repair, replacement or credit — is determined by the manufacturer terms for that product. See '.$this->link('/page/customer-service', 'Customer Service').' for the claim procedure and what to have ready.'),
                        $this->p('Warranties do not cover accidental damage, liquid ingress, damage from unstable power supply, misuse, unauthorised repair, or normal wear such as battery capacity decline over time.'),
                    ]),

                    $this->section('6. How to Start a Return', [
                        $this->ol([
                            '<strong>Contact us within 14 days.</strong> Email '.self::EMAIL.' or use '.$this->link('/contact-us', 'the contact form').', quoting your order number, the item, and the reason for return.',
                            '<strong>Wait for your return authorisation.</strong> We reply within 1 business day with an RMA reference and the return address or collection arrangement.',
                            '<strong>Pack the item properly.</strong> Place the original product box inside an outer shipping carton and write the RMA reference on the <em>outer</em> carton only.',
                            '<strong>Ship it or drop it off.</strong> Use a trackable service, or bring it to our showroom during opening hours.',
                            '<strong>We inspect and confirm.</strong> Inspection takes up to 3 business days from arrival, after which we email you the outcome.',
                        ]),
                        $this->callout('Do not send anything back without an RMA reference. Unauthorised returns cannot be matched to an order, may be refused by our receiving desk, and are returned to sender at your cost.'),
                    ]),

                    $this->section('7. Who Pays the Return Shipping', [
                        $this->table(
                            ['Reason for return', 'Outbound shipping', 'Return shipping'],
                            [
                                ['Item faulty, damaged in transit, or wrong item sent', 'Refunded', 'We pay'],
                                ['Item not as described on our site', 'Refunded', 'We pay'],
                                ['Change of mind, no longer needed, ordered in error', 'Not refunded', 'You pay'],
                                ['Compatibility not checked before ordering', 'Not refunded', 'You pay'],
                            ]
                        ),
                        $this->p('Until a returned parcel reaches us it remains your responsibility, so please use a trackable service and keep the receipt. We cannot refund an item we do not receive.'),
                    ]),

                    $this->section('8. Inspection and Outcomes', [
                        $this->p('Every return is inspected against the conditions in section 2. One of the following then applies:'),
                        $this->ul([
                            '<strong>Accepted in full</strong> — refund or exchange processed as described in our '.$this->link('/page/refund-policy', 'Refund Policy').'.',
                            '<strong>Accepted with a deduction</strong> — where the item or its packaging is incomplete or damaged beyond normal inspection, we apply a deduction reflecting the loss of value. We tell you the amount and the reason before processing it, and you may ask for the item back instead.',
                            '<strong>Declined</strong> — where the item falls outside the return window, is non-returnable, or arrives in a condition that cannot be resold. We will explain why and return the item to you at your cost.',
                        ]),
                    ]),

                    $this->section('9. Exchanges', [
                        $this->p('To exchange an item, say so when you request your RMA. We will reserve the replacement where stock allows. Any price difference is refunded or invoiced when the exchange is processed. If the replacement is unavailable, we refund instead and tell you the expected restock date.'),
                    ]),

                    $this->section('10. Before You Send a Device Back', [
                        $this->ul([
                            '<strong>Back up your data.</strong> We are not responsible for data loss on any returned or serviced device.',
                            '<strong>Erase your data</strong> and remove any drive you wish to keep, noting that removing a drive may itself breach section 2 — ask us first.',
                            '<strong>Sign out and unlink</strong> all accounts, and remove any activation lock or device-management enrolment. A locked device cannot be resold and cannot be refunded.',
                            '<strong>Remove SIMs, memory cards and accessories</strong> that were not supplied with the product.',
                        ]),
                    ]),

                    $this->contactSection('To start a return or ask whether an item qualifies:'),
                ]
            ),
        ];
    }

    protected function refundPolicy(): array
    {
        return [
            'title' => 'Refund Policy',
            'meta_title' => 'Refund Policy — How & When Refunds Are Issued | ZoneTec',
            'meta_description' => 'ZoneTec refund policy: eligibility, refund timelines by payment method, what is refunded, partial refunds and cancelled orders.',
            'meta_keywords' => 'refund policy, refunds, money back, cancelled order refund, refund timeline',
            'content' => $this->page(
                'Refund Policy',
                'When you are entitled to a refund, how much is refunded, and how long the money takes to reach you. Read this alongside our Return Policy, which covers sending the item back.',
                [
                    $this->section('1. Overview', [
                        $this->p('Refunds are issued to the <strong>original payment method</strong> used for the order. We do not refund to a different card, account or person, and we do not issue cash refunds for online orders.'),
                        $this->p('We approve or decline every refund in writing, with the amount and the reason stated. If we deduct anything, we tell you why before the refund is processed.'),
                    ]),

                    $this->section('2. When You Are Entitled to a Refund', [
                        $this->ul([
                            'You cancelled an order <strong>before dispatch</strong> — refunded in full, including any shipping charge.',
                            'You returned an item within the <strong>14-day window</strong> and it met the conditions in our '.$this->link('/page/return-policy', 'Return Policy').'.',
                            'The item arrived <strong>faulty, damaged or incorrect</strong> — refunded in full, including shipping in both directions.',
                            'The item was <strong>not as described</strong> on our site.',
                            'We <strong>could not fulfil</strong> your order, or cancelled it because stock was unavailable or verification failed.',
                            'Your parcel was <strong>confirmed lost</strong> in transit and you chose a refund over a replacement.',
                        ]),
                    ]),

                    $this->section('3. What Is Refunded', [
                        $this->table(
                            ['Situation', 'Product price', 'Original shipping', 'Return shipping'],
                            [
                                ['Order cancelled before dispatch', 'Full', 'Full', 'Not applicable'],
                                ['Faulty, damaged or wrong item', 'Full', 'Full', 'Refunded / we collect'],
                                ['Not as described', 'Full', 'Full', 'Refunded'],
                                ['Change of mind, within 14 days', 'Full', 'Not refunded', 'At your cost'],
                                ['Returned incomplete or damaged', 'Reduced — see section 5', 'Not refunded', 'At your cost'],
                                ['Order lost in transit', 'Full', 'Full', 'Not applicable'],
                            ]
                        ),
                        $this->p('Where a promotional discount, bundle price or free item was tied to the order, the refund is calculated on the amount actually paid. If a return breaks the qualifying condition for a bundle or a free gift, the value of that benefit is deducted, or the gift must be returned with the item.'),
                    ]),

                    $this->section('4. Refund Timelines', [
                        $this->p('We process approved refunds within <strong>3 business days</strong> of approval. How long the money then takes to appear depends on your payment provider, not on us.'),
                        $this->table(
                            ['Original payment method', 'Time to appear after we process it'],
                            [
                                ['Credit or debit card', '5–10 business days, set by your card issuer'],
                                ['PayPal', '1–3 business days to your PayPal balance'],
                                ['Bank / money transfer', '3–7 business days to the account you transferred from'],
                                ['Cash on delivery', '3–7 business days by bank transfer to details you provide'],
                                ['Store credit', 'Immediate'],
                            ]
                        ),
                        $this->p('Card refunds are returned along the same route as the original payment, so they can appear as a reversal of the original transaction rather than as a new credit line. If a card refund has not appeared after 10 business days, contact us and we will send you the processor reference to give to your bank.'),
                        $this->callout('Cash-on-delivery refunds require bank details from you, since there is no payment route to reverse. We will only ever ask for these by email from our own address, and we will never ask for your online-banking password, PIN or a card CVV.'),
                    ]),

                    $this->section('5. Partial Refunds', [
                        $this->p('We may refund less than the full amount where a returned item has lost value in your hands. Typical deductions:'),
                        $this->ul([
                            'Missing accessories, cables, adapters, brackets, manuals or licence cards.',
                            'Missing, destroyed or heavily damaged original packaging.',
                            'Cosmetic damage — scratches, marks, dents — beyond what inspection requires.',
                            'Thermal-paste residue, bent pins, or evidence of permanent installation.',
                            'Broken warranty or serial-number seals.',
                        ]),
                        $this->p('The deduction reflects the actual reduction in resale value. We notify you of the amount and the reason first, and you may choose to have the item returned to you instead, at your cost.'),
                    ]),

                    $this->section('6. Price Adjustments', [
                        $this->p('If an item you bought is reduced in price within <strong>7 calendar days</strong> of your order, contact us and we will credit the difference as store credit or refund it to your original payment method. This does not apply to clearance stock, time-limited flash sales, bundle pricing, or items bought with a promotional code.'),
                    ]),

                    $this->section('7. Cancelled and Undelivered Orders', [
                        $this->ul([
                            '<strong>You cancel before dispatch</strong> — full refund, no deduction, processed within 3 business days.',
                            '<strong>We cancel</strong> for stock, pricing or verification reasons — full refund, and we will offer an alternative where one exists.',
                            '<strong>Delivery refused without cause</strong> — refund of the product price only. Outbound and return freight are deducted.',
                            '<strong>Parcel unclaimed and returned to us</strong> — refund of the product price once the parcel is received and inspected, less the return freight cost.',
                            '<strong>International shipment abandoned at customs</strong> — refund of the product price less all freight, duty and clearance charges incurred.',
                        ]),
                    ]),

                    $this->section('8. What Is Not Refundable', [
                        $this->ul([
                            'The non-returnable goods listed in section 3 of our '.$this->link('/page/return-policy', 'Return Policy').', unless faulty.',
                            'Shipping charges on a change-of-mind return.',
                            'Bank, transfer and currency-conversion fees charged by your own bank or by PayPal.',
                            'Import duties and customs clearance fees already paid on an international shipment.',
                            'Installation, configuration or on-site services already performed.',
                            'Losses beyond the price of the goods — downtime, lost work, lost profit or consequential loss.',
                        ]),
                    ]),

                    $this->section('9. How to Request a Refund', [
                        $this->ol([
                            'Email '.self::EMAIL.' or use '.$this->link('/contact-us', 'the contact form').' with your <strong>order number</strong> and what you want refunded.',
                            'If the item needs to come back, we issue an RMA reference — follow our '.$this->link('/page/return-policy', 'Return Policy').'.',
                            'We inspect on arrival (up to 3 business days) and email you the decision.',
                            'On approval we process the refund within 3 business days and send you a confirmation with the reference.',
                        ]),
                        $this->p('Refunds and credit notes are also recorded against your order under '.$this->link('/customer/account/orders', 'My Account &rarr; Orders').'.'),
                    ]),

                    $this->section('10. Disagreeing With a Decision', [
                        $this->p('If you believe a refund decision is wrong, reply to our email and ask for it to be escalated. A supervisor will review the file — including the inspection notes and photographs — and respond with a final decision and an explanation within <strong>3 business days</strong>. Nothing in this policy affects your statutory rights under applicable Lebanese law.'),
                    ]),

                    $this->contactSection('To chase a refund or query an amount:'),
                ]
            ),
        ];
    }

    protected function termsConditions(): array
    {
        return [
            'title' => 'Terms & Conditions',
            'meta_title' => 'Terms & Conditions of Sale — ZoneTec',
            'meta_description' => 'The terms governing your use of the ZoneTec store and your purchase of products: orders, pricing, payment, delivery, warranties, liability and governing law.',
            'meta_keywords' => 'terms and conditions, terms of sale, conditions of use, legal',
            'content' => $this->page(
                'Terms &amp; Conditions',
                'These terms govern your use of this website and every order you place with us. Please read them before you buy — placing an order means you accept them.',
                [
                    $this->section('1. About These Terms', [
                        $this->p('This website is operated by '.self::COMPANY.' ("ZoneTec", "we", "us", "our"), registered at '.self::ADDRESS.' under commercial register '.self::REGISTER.'.'),
                        $this->p('By accessing this website, creating an account or placing an order, you agree to be bound by these Terms &amp; Conditions together with our '.$this->link('/page/privacy-policy', 'Privacy Policy').', '.$this->link('/page/shipping-policy', 'Shipping Policy').', '.$this->link('/page/payment-policy', 'Payment Policy').', '.$this->link('/page/return-policy', 'Return Policy').' and '.$this->link('/page/refund-policy', 'Refund Policy').', each of which forms part of these terms. If you do not accept them, please do not use the site.'),
                    ]),

                    $this->section('2. Definitions', [
                        $this->ul([
                            '<strong>"Site"</strong> — this website and any subdomain or application we operate.',
                            '<strong>"Products"</strong> — the goods and services offered for sale on the Site.',
                            '<strong>"Order"</strong> — your offer to purchase Products, submitted through the Site.',
                            '<strong>"Contract"</strong> — the binding agreement formed when we accept your Order under clause 5.',
                            '<strong>"You"</strong> — the person placing the Order, whether as a consumer or on behalf of a business.',
                        ]),
                    ]),

                    $this->section('3. Eligibility and Your Account', [
                        $this->ul([
                            'You must be at least 18 years old, or have the consent of a parent or guardian, to place an order.',
                            'Information you give us — name, address, contact details, payment details — must be accurate, current and complete.',
                            'You are responsible for keeping your account password confidential and for all activity under your account.',
                            'Tell us immediately at '.self::EMAIL.' if you believe your account has been accessed without your authority.',
                            'We may suspend or close an account that is used in breach of these terms, that we reasonably believe is fraudulent, or that is used for unauthorised commercial resale.',
                        ]),
                    ]),

                    $this->section('4. Products, Descriptions and Availability', [
                        $this->p('We describe our products as accurately as we can. Specifications, images and packaging are supplied largely by manufacturers and may change without notice; images are illustrative and may not depict the exact revision, colour or bundled accessories supplied.'),
                        $this->ul([
                            'A listing is an invitation to buy, not a binding offer of sale.',
                            'Stock levels change constantly and are not guaranteed until an order is accepted.',
                            'Manufacturers may revise components, firmware and packaging within the same model number.',
                            'Performance figures — clock speeds, transfer rates, battery life — are manufacturer claims measured under their conditions and will vary in real use.',
                            'Where a product includes third-party software, its own licence terms apply between you and the licensor.',
                        ]),
                        $this->p('If you rely on a specific technical detail, please confirm it with us in writing before ordering. We are glad to check.'),
                    ]),

                    $this->section('5. Orders and Acceptance', [
                        $this->p('The order confirmation email we send when you check out acknowledges receipt of your order. It is not acceptance.'),
                        $this->p('A Contract is formed only when we dispatch the Products, or when we confirm acceptance of your order in writing, whichever happens first. Until then we may decline an order — in whole or in part — for any of the following reasons:'),
                        $this->ul([
                            'The Products are unavailable or have been discontinued.',
                            'There was an error in the price or the description.',
                            'We are unable to verify your payment method, identity or delivery address.',
                            'We cannot deliver to your address, or delivery of that Product there is restricted.',
                            'We reasonably suspect fraudulent, abusive or unauthorised resale activity.',
                        ]),
                        $this->p('Where we decline an order, we notify you and refund any amount taken in full.'),
                    ]),

                    $this->section('6. Prices and Payment', [
                        $this->ul([
                            'All prices are in <strong>US dollars (USD)</strong> and may change at any time before your order is accepted.',
                            'Applicable taxes and shipping charges are calculated and shown in full before you confirm payment.',
                            'You must pay by one of the methods set out in our '.$this->link('/page/payment-policy', 'Payment Policy').'.',
                            'Products remain our property until we receive payment in full.',
                            'Where a price is obviously incorrect, we are not obliged to supply at that price and clause 5 applies.',
                        ]),
                    ]),

                    $this->section('7. Delivery and Risk', [
                        $this->p('Delivery is made in accordance with our '.$this->link('/page/shipping-policy', 'Shipping Policy').'. Delivery dates are estimates and are not guaranteed.'),
                        $this->p('Risk in the Products passes to you on delivery to the address you specified, or on collection. You must inspect the packaging on arrival and report visible damage within 48 hours as set out in the Shipping Policy. Title passes to you on delivery or on receipt of full payment, whichever is later.'),
                    ]),

                    $this->section('8. Cancellation, Returns and Refunds', [
                        $this->p('Your cancellation and return rights are set out in full in our '.$this->link('/page/return-policy', 'Return Policy').' and '.$this->link('/page/refund-policy', 'Refund Policy').'. In summary: orders may be cancelled free of charge before dispatch, and most Products may be returned within 14 days of delivery in their original condition and packaging. Certain Products — custom builds, activated software licences, opened consumables and hygiene-sealed goods — are excluded, and nothing in those policies limits your statutory rights where a Product is faulty or not as described.'),
                    ]),

                    $this->section('9. Warranties', [
                        $this->p('Products are covered by the manufacturer warranty stated on their product page, and we will administer warranty claims on your behalf. The remedy available — repair, replacement or credit — is set by the manufacturer terms for that Product.'),
                        $this->p('Except as expressly stated in these terms and as required by applicable law, all other warranties, conditions and representations, whether express or implied, are excluded. In particular, warranties do not cover:'),
                        $this->ul([
                            'Accidental damage, drops, impact or liquid ingress.',
                            'Damage caused by unstable mains power, incorrect voltage or lightning.',
                            'Misuse, neglect, overclocking beyond manufacturer specification, or use outside stated environmental limits.',
                            'Unauthorised opening, modification or repair, or broken warranty seals.',
                            'Consumable wear, including battery capacity decline and mechanical wear on moving parts.',
                            'Software faults, configuration problems, malware, and loss of data.',
                        ]),
                    ]),

                    $this->section('10. Software and Licensed Content', [
                        $this->p('Software, operating systems, licence keys and digital content are licensed to you by their publisher, not sold by us. Your use is governed by the publisher\'s licence agreement. Once a key has been revealed, redeemed or activated it cannot be returned or refunded. We are not responsible for a publisher\'s decision to suspend, revoke or change the terms of a licence.'),
                    ]),

                    $this->section('11. Acceptable Use of the Site', [
                        $this->p('You agree not to:'),
                        $this->ul([
                            'Use the Site unlawfully, fraudulently, or in breach of these terms.',
                            'Scrape, crawl, mirror or systematically extract content, pricing or stock data without our written consent.',
                            'Attempt to gain unauthorised access to the Site, its accounts, its servers or its infrastructure.',
                            'Introduce malware, or attempt to interfere with the availability or integrity of the Site.',
                            'Place speculative or fraudulent orders, or orders under a false identity.',
                            'Reverse-engineer, decompile or copy any part of the Site.',
                        ]),
                    ]),

                    $this->section('12. Intellectual Property', [
                        $this->p('All content on this Site — text, layout, graphics, logos, icons, images, product copy, software and their arrangement — is owned by us or our licensors and is protected by intellectual property law. You may view and print pages for your own non-commercial use. You may not otherwise copy, republish, distribute, sell or exploit any part of it without our prior written consent. Third-party trademarks and product images remain the property of their owners and are used to identify the Products we sell.'),
                    ]),

                    $this->section('13. Reviews and Submitted Content', [
                        $this->p('If you submit a review, question, image or other content, you grant us a non-exclusive, royalty-free, worldwide licence to use, reproduce and display it in connection with the Site. You confirm that the content is your own, is accurate, and does not infringe anyone\'s rights.'),
                        $this->p('We may moderate, edit or remove content that is unlawful, defamatory, misleading, offensive, off-topic, promotional or otherwise inappropriate. We do not endorse and are not responsible for opinions expressed in customer content.'),
                    ]),

                    $this->section('14. Third-Party Links and Services', [
                        $this->p('The Site may link to or embed third-party websites and services — payment processors, carriers, manufacturer resources. We do not control them and are not responsible for their content, availability, security or privacy practices. Your use of a third-party service is governed by that provider\'s own terms.'),
                    ]),

                    $this->section('15. Limitation of Liability', [
                        $this->p('Nothing in these terms excludes or limits our liability for death or personal injury caused by our negligence, for fraud or fraudulent misrepresentation, or for any other liability that cannot lawfully be excluded.'),
                        $this->p('Subject to that:'),
                        $this->ul([
                            'Our total liability arising out of or in connection with any Contract shall not exceed the total amount you paid for the Products under that Contract.',
                            'We are not liable for indirect or consequential loss, including loss of profit, loss of business, loss of revenue, loss of goodwill, business interruption or downtime.',
                            'We are not liable for loss or corruption of data. You are responsible for maintaining adequate backups at all times.',
                            'We are not liable for losses arising from your failure to follow manufacturer instructions, or from installation or configuration carried out by you or a third party.',
                            'We do not guarantee that the Site will be uninterrupted, error-free or free of harmful components.',
                        ]),
                    ]),

                    $this->section('16. Indemnity', [
                        $this->p('You agree to indemnify us against any claim, loss, liability, cost or expense (including reasonable legal fees) arising from your breach of these terms, your unlawful use of the Site, or your infringement of a third party\'s rights.'),
                    ]),

                    $this->section('17. Force Majeure', [
                        $this->p('We are not liable for any delay or failure to perform caused by events beyond our reasonable control, including acts of God, war, civil unrest, strikes, fire, flood, epidemic, currency restrictions, power or telecommunications failure, cyber-attack, import restrictions, carrier failure, or the acts of any government or public authority. Where such an event continues for more than 30 days, either party may cancel the affected Contract and we will refund any amount paid for undelivered Products.'),
                    ]),

                    $this->section('18. Business Customers', [
                        $this->p('Where you buy in the course of a business, you confirm you have authority to bind that business, and the consumer-specific provisions of applicable law do not apply to you. Any separate written agreement, quotation or account terms agreed between us take precedence over these terms to the extent of any conflict.'),
                    ]),

                    $this->section('19. Changes to These Terms', [
                        $this->p('We may amend these terms from time to time. The version published on this page when you place an order is the version that applies to that order, so please review it each time you buy. Material changes will be reflected in the "last updated" date at the top of this page.'),
                    ]),

                    $this->section('20. Severability, Waiver and Assignment', [
                        $this->ul([
                            'If any provision is found to be unenforceable, the remaining provisions continue in full force.',
                            'A failure or delay by us in enforcing a right is not a waiver of that right.',
                            'You may not assign or transfer your rights under a Contract without our written consent. We may assign ours to a successor or acquirer of our business.',
                            'These terms, together with the policies referenced in clause 1, constitute the entire agreement between us regarding your order.',
                        ]),
                    ]),

                    $this->section('21. Governing Law and Jurisdiction', [
                        $this->p('These terms, and any Contract formed under them, are governed by the laws of the <strong>Republic of Lebanon</strong>. The competent courts of <strong>Beirut, Lebanon</strong> have exclusive jurisdiction over any dispute arising out of or in connection with them.'),
                        $this->p('Before commencing proceedings, we ask that you contact us at '.self::EMAIL.' so that we have a genuine opportunity to resolve the matter directly. Most disputes are settled far more quickly that way.'),
                    ]),

                    $this->contactSection('For any question about these terms:'),
                ]
            ),
        ];
    }

    protected function privacyPolicy(): array
    {
        return [
            'title' => 'Privacy Policy',
            'meta_title' => 'Privacy Policy — How ZoneTec Handles Your Data',
            'meta_description' => 'What personal data ZoneTec collects, why we collect it, who we share it with, how long we keep it, how we secure it, and the rights you have over it.',
            'meta_keywords' => 'privacy policy, data protection, personal data, cookies, your rights',
            'content' => $this->page(
                'Privacy Policy',
                'This policy explains what personal information we collect when you use our store, why we need it, who we share it with, and the control you have over it. We do not sell your data.',
                [
                    $this->section('1. Scope', [
                        $this->p('This policy applies to personal data we process when you visit this website, create an account, place an order, subscribe to our newsletter, submit a review, or contact our support team. It does not cover third-party websites we link to, each of which has its own policy.'),
                    ]),

                    $this->section('2. Who Is Responsible for Your Data', [
                        $this->p('The data controller is:'),
                        $this->ul([
                            '<strong>Entity:</strong> '.self::COMPANY,
                            '<strong>Commercial register:</strong> '.self::REGISTER,
                            '<strong>Address:</strong> '.self::ADDRESS,
                            '<strong>Privacy contact:</strong> '.self::EMAIL,
                        ]),
                    ]),

                    $this->section('3. What We Collect', [
                        $this->h3('Information you give us'),
                        $this->table(
                            ['Category', 'Examples', 'When we collect it'],
                            [
                                ['Identity', 'Name, and company name where relevant', 'Registration, checkout, enquiries'],
                                ['Contact', 'Email address, telephone number, billing and delivery addresses', 'Registration, checkout, enquiries'],
                                ['Account', 'Username, hashed password, saved addresses, wishlists', 'Registration and account use'],
                                ['Order', 'Products ordered, order history, invoices, returns and warranty records', 'Placing and servicing orders'],
                                ['Payment', 'Payment method type, last four digits, authorisation result, transfer receipts', 'Checkout, via our processors'],
                                ['Communications', 'Emails, contact-form messages, call and chat notes, review content', 'When you contact us or post'],
                                ['Marketing', 'Newsletter subscription status and preferences', 'When you subscribe or opt out'],
                            ]
                        ),

                        $this->h3('Information collected automatically'),
                        $this->ul([
                            'IP address, approximate location derived from it, and language preference.',
                            'Device and browser type, operating system, and screen characteristics.',
                            'Pages viewed, products viewed, search terms used, referring page and time spent.',
                            'Basket and session identifiers held in cookies so your basket survives page changes.',
                        ]),

                        $this->h3('Information from third parties'),
                        $this->ul([
                            'Payment and fraud-screening outcomes from our payment processors.',
                            'Delivery status and proof of delivery from carriers.',
                            'Basic profile data if you choose to sign in or pay via a third-party service such as PayPal.',
                        ]),
                        $this->callout('We do not knowingly collect special-category data (health, religion, political opinion, biometrics) and we ask you not to send it to us. Please do not include payment card numbers in an email or contact-form message.'),
                    ]),

                    $this->section('4. Why We Use It, and Our Legal Basis', [
                        $this->table(
                            ['Purpose', 'Data used', 'Basis'],
                            [
                                ['Process and deliver your order, and handle returns and warranty claims', 'Identity, contact, order, payment', 'Performance of our contract with you'],
                                ['Take payment and prevent fraud', 'Payment, order, technical', 'Contract, and our legitimate interest in preventing fraud'],
                                ['Manage your account and support requests', 'Account, contact, communications', 'Contract'],
                                ['Send order, dispatch and service notifications', 'Contact, order', 'Contract'],
                                ['Send marketing emails and newsletters', 'Contact, marketing preferences', 'Your consent, withdrawable at any time'],
                                ['Improve the site, our range and our service', 'Technical, usage, aggregated order data', 'Our legitimate interest in running the business well'],
                                ['Keep accounting, tax and warranty records', 'Identity, order, payment', 'Compliance with our legal obligations'],
                                ['Secure the site and investigate misuse', 'Technical, account, usage', 'Legitimate interest in protecting our systems and customers'],
                            ]
                        ),
                    ]),

                    $this->section('5. Cookies and Similar Technologies', [
                        $this->p('We use cookies and comparable browser storage for the following purposes:'),
                        $this->ul([
                            '<strong>Strictly necessary</strong> — sign-in sessions, shopping basket, checkout steps, security tokens. The store cannot function without these.',
                            '<strong>Preference</strong> — remembering your locale, currency and display choices.',
                            '<strong>Analytics</strong> — understanding, in aggregate, which pages and products people use, so we can improve them.',
                            '<strong>Marketing</strong> — where enabled, measuring campaign performance and showing relevant offers.',
                        ]),
                        $this->p('You can block or delete cookies in your browser settings. Blocking strictly necessary cookies will prevent you from signing in or completing checkout. Where consent is required for analytics or marketing cookies, we ask for it before setting them, and you can change your mind at any time.'),
                    ]),

                    $this->section('6. Who We Share It With', [
                        $this->p('We share personal data only where it is needed to run the store, and only to the extent necessary. We <strong>never sell</strong> your personal data.'),
                        $this->table(
                            ['Recipient', 'What they receive', 'Why'],
                            [
                                ['Payment processors (e.g. Stripe, PayPal)', 'Payment and billing details, order amount', 'To take payment and screen for fraud'],
                                ['Delivery carriers', 'Name, delivery address, telephone number, order reference', 'To deliver your parcel and contact you about it'],
                                ['Manufacturers and authorised service centres', 'Product, serial number, fault description, and your contact details where needed', 'To process warranty and service claims'],
                                ['Email and hosting providers', 'Data necessary to run the site and send transactional and marketing email', 'Infrastructure and communications'],
                                ['Analytics providers', 'Pseudonymised usage and device data', 'To measure and improve the site'],
                                ['Professional advisers and authorities', 'Only what is legally required', 'Accounting, audit, legal obligations, lawful requests'],
                            ]
                        ),
                        $this->p('Our service providers act on our instructions under contract, and are not permitted to use your data for their own purposes. We may also disclose data where required by law, to enforce our terms, or in connection with a sale or restructuring of our business — in which case the recipient remains bound by this policy.'),
                    ]),

                    $this->section('7. Payment Card Data', [
                        $this->p('We do not see, process or store full payment card numbers, expiry dates or security codes. Card details are submitted directly to a PCI-DSS compliant payment processor over an encrypted connection. We retain only the payment method type, the last four digits, and the authorisation result — enough to identify a transaction, support a refund and answer a query.'),
                    ]),

                    $this->section('8. How Long We Keep It', [
                        $this->table(
                            ['Record', 'Retention period'],
                            [
                                ['Order, invoice and tax records', 'As required by applicable accounting and tax law, typically 10 years'],
                                ['Warranty and service records', 'For the warranty period plus 2 years'],
                                ['Account data', 'While your account is active, then up to 24 months after last activity'],
                                ['Support correspondence', 'Up to 3 years from case closure'],
                                ['Newsletter subscription', 'Until you unsubscribe, plus a suppression record so we do not email you again'],
                                ['Analytics and server logs', 'Up to 26 months, in aggregated or pseudonymised form'],
                            ]
                        ),
                        $this->p('When a retention period ends we delete or irreversibly anonymise the data.'),
                    ]),

                    $this->section('9. How We Protect It', [
                        $this->ul([
                            'All traffic to and from the site is encrypted with TLS (HTTPS).',
                            'Passwords are stored only as salted one-way hashes; nobody at ZoneTec can read your password.',
                            'Administrative access is restricted by role, granted on a least-privilege basis, and logged.',
                            'Payment processing is delegated to PCI-DSS compliant providers, keeping card data out of our systems.',
                            'Systems and dependencies are patched, and backups are held securely.',
                        ]),
                        $this->p('No system can be guaranteed completely secure. If a breach occurs that is likely to affect your rights, we will notify you and the relevant authority without undue delay.'),
                    ]),

                    $this->section('10. Your Rights', [
                        $this->p('Subject to applicable law, you may ask us to:'),
                        $this->ul([
                            '<strong>Access</strong> the personal data we hold about you, and receive a copy.',
                            '<strong>Correct</strong> data that is inaccurate or incomplete.',
                            '<strong>Delete</strong> data we no longer have a lawful reason to keep.',
                            '<strong>Restrict or object</strong> to certain processing, including processing based on legitimate interest.',
                            '<strong>Port</strong> data you gave us to another provider in a machine-readable format.',
                            '<strong>Withdraw consent</strong> for marketing at any time, without affecting anything done before.',
                        ]),
                        $this->p('You can update most details yourself under '.$this->link('/customer/account/profile', 'My Account &rarr; Profile').'. For anything else, email '.self::EMAIL.' from the address on your account. We respond within <strong>30 days</strong> and may need to verify your identity first. Note that we cannot delete records we are legally obliged to retain, such as tax invoices.'),
                    ]),

                    $this->section('11. Marketing Preferences', [
                        $this->p('We send marketing email only where you have opted in. Every marketing email carries an unsubscribe link that takes effect immediately, and you can also email '.self::EMAIL.' to opt out.'),
                        $this->p('Transactional messages — order confirmations, dispatch and delivery notices, invoices, warranty updates and security notifications — are part of servicing your order and are sent regardless of marketing preferences.'),
                    ]),

                    $this->section('12. Children', [
                        $this->p('This store is not directed at children under 18 and we do not knowingly collect their personal data. If you believe a child has provided us with personal information, contact '.self::EMAIL.' and we will delete it.'),
                    ]),

                    $this->section('13. International Transfers', [
                        $this->p('Some of our service providers — hosting, email delivery, payment processing, analytics — operate outside Lebanon. Where personal data is transferred internationally, we require appropriate contractual safeguards obliging the recipient to protect it to a standard consistent with this policy and applicable law.'),
                    ]),

                    $this->section('14. Automated Decision-Making', [
                        $this->p('We use automated fraud screening at checkout, which may flag an order for manual review or decline it. High-value and unusual orders are always reviewed by a person before a final decision. If an order of yours is declined on these grounds, contact us and we will look at it again manually.'),
                    ]),

                    $this->section('15. Changes to This Policy', [
                        $this->p('We may update this policy to reflect changes in our practices or in the law. The current version is always published here, with the "last updated" date at the top. Where a change materially affects your rights, we will take reasonable steps to notify you directly.'),
                    ]),

                    $this->contactSection('To exercise a right, or to ask anything about how we handle your data:'),
                ]
            ),
        ];
    }
}
