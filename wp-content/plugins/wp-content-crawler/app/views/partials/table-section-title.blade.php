{{--
    Required variables:
        string $key  An identifier unique among all the other section title identifiers
--}}
<tr data-id="{{ $key }}"
    class="section-header {{ $class ?? '' }}"
>
    <td colspan="2">
        <div class="section-header-wrapper">
            {{-- Controls --}}
            <div class="controls">
                {{-- Expansion state indicator --}}
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>

            {{-- Head --}}
            <div class="head">
                {{-- Section title --}}
                <h4 class="section-title">{!! $title !!}</h4>
                {{-- Section info --}}
                <div class="info"></div>
            </div>
        </div>
    </td>
</tr>