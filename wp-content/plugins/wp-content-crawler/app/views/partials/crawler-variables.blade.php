{{--
    Required variables:
        CrawlerVariable[] $variables The variable definitions
--}}

<?php
use WPCCrawler\Objects\Crawling\Bot\Objects\CrawlerVariable;

/** @var CrawlerVariable[] $variables */
?>

<div id="wpcc-variables" class="wpcc-generated-container">
    <div class="wpcc-head">
        <div class="wpcc-title">{{ _wpcc('WP Content Crawler Variables') }}</div>
        <div class="wpcc-desc">{{ _wpcc('These variables might be handy when crawling') }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th>{{ _wpcc('Name') }}</th>
            <th>{{ _wpcc('Value') }}</th>
        </tr>
        </thead>
        <tbody>

        @foreach($variables as $variable)
            <tr>
                {{-- Name of the variable --}}
                <td>{{ $variable->getName() }}</td>

                {{-- Value of the variable --}}
                <td class="{{ $variable->getCssClass() }}">
                    {{ $variable->getValue() }}
                </td>
            </tr>
        @endforeach

        </tbody>
    </table>
</div>