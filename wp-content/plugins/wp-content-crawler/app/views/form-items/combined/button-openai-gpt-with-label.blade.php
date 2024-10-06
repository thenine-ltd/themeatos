{{--
    Required variables:
        String $title: Title of the form item. Label name.
        String $info: Information about the form item
        String $name: Name of the form item

    Optional variables:
        String $id: ID of the <tr> element surrounding the form items
        String $class: Class of the <tr> element surrounding the form items.
        Other variables of label and OpenAI GPT button form item views.

--}}

<tr @if(isset($id)) id="{{ $id }}" @endif
@if(isset($class)) class="{{ $class }}" @endif
>
    <td>
        @include('form-items/label', [
            'for'   => $name,
            'title' => $title,
            'info'  => $info,
        ])
    </td>
    <td>
        @include('form-items.partials.button-openai-gpt')
    </td>
</tr>