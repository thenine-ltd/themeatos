{{--
    Optional variables:
        bool $sortableSelect  If this is `true`, the "select" element's options will be sortable by the user by clicking
                              a button
--}}

<?php
    /** @var string $name */
    /** @var array $options */
?>
<div class="input-group {{ isset($remove) ? 'remove' : '' }}"
     @if(isset($dataKey)) data-key="{{ $dataKey }}" @endif
>
    <div class="input-container">
        @include('form-items.select-element')
    </div>

    @if(isset($remove) && $remove)
        @include('form-items.remove-button')
    @endif
</div>