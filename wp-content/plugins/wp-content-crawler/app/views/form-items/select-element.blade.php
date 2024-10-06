{{--
    Optional variables:
        bool $sortableSelect  If this is `true`, the "select" element's options will be sortable by the user by clicking
                              a button
--}}

<?php
/** @var string $name */
/** @var array<string, array|string> $options */
?>

<select name="{{ $name }}"
        id="{{ $name }}"
        tabindex="0"
        @if(isset($selectTitle)) title="{{ $selectTitle }}" @endif
        @if(isset($disabled)) disabled @endif
>
    <?php $selectedKey = isset($settings[$name]) ? (isset($isOption) && $isOption ? $settings[$name] : $settings[$name][0]) : false; ?>
    @foreach($options as $key => $optionData)
        <?php
            /** @var string|array $optionData */
            // If the option data is an array
            $isArr = is_array($optionData);
            if ($isArr) {
                // Get the option name and the dependants if there exists any
                $optionName = \WPCCrawler\Utils::array_get($optionData, 'name');
                $dependants = \WPCCrawler\Utils::array_get($optionData, 'dependants');
                $container  = \WPCCrawler\Utils::array_get($optionData, 'container');
            } else {
                // Otherwise, option data is the name of the option and there is no dependant.
                $optionName = $optionData;
                $dependants = null;
                $container = null;
            }
        ?>

        <option value="{{ $key }}" data-order="{{ $loop->index }}"
                @if($selectedKey && $key == $selectedKey) selected="selected" @endif
                @if($dependants) data-dependants="{{ $dependants }}" @endif
                @if($container) data-container="{{ $container }}" @endif
        >{{ $optionName }}</option>
    @endforeach
</select>

{{-- If this should be sortable, add the sorter --}}
@if($sortableSelect ?? false)
    @include('partials.select-sorter')
@endif