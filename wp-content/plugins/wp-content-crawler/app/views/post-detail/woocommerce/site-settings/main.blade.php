{{-- SECTION: WOOCOMMERCE --}}
@include('partials.table-section-title', [
    'title' => _wpcc("WooCommerce"),
    'key'   => \WPCCrawler\Objects\Enums\SectionKey::SITE_SETTINGS_POST_WOOCOMMERCE,
])

<tr id="woocommerce-options-container">
    <td colspan="2">
        @include('post-detail.woocommerce.site-settings.container')
    </td>
</tr>