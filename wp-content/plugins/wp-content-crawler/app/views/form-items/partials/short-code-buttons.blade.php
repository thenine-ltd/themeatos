@if(isset($buttons))
    <div class="input-group">
        <div class="input-container input-button-container short-code-container">
                <?php /** @var \WPCCrawler\Objects\ShortCode\ShortCodeButton $button */ ?>
            @foreach($buttons as $button)
                <button class="button wpcc-button" type="button"
                        data-shortcode-name="{{ $button->getCode() }}"
                        data-clipboard-text="{{ $button->getCodeWithBrackets() }}"
                        data-wpcc-toggle="wpcc-tooltip"
                        data-placement="{{ isset($tooltipPos) && $tooltipPos ? $tooltipPos : 'top' }}"
                        title="{{ $button->getDescription() }}"
                >{{ $button->getCodeWithBrackets() }}</button>
            @endforeach

            @if(!isset($noGenerativeShortCodes) || !$noGenerativeShortCodes)
                <div class="generative-short-code-container">
                    @include('form-items.partials.button-openai-gpt')
                </div>
            @endif

            @if(!isset($noCustomShortCodes) || !$noCustomShortCodes)
                <div class="custom-short-code-container"></div>
            @endif

            @if(isset($localCustomShortCodes) && $localCustomShortCodes)
                <div class="local-short-code-container"></div>
            @endif

        </div>
    </div>
@endif