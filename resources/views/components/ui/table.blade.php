@props([
    'headers' => [],
    'rows' => [],
    'footers' => null,
    'actions' => null,
    'striped' => false,
    'bordered' => true,
    'hoverable' => true,
    'compact' => true,
])

@php
    $baseClasses = 'min-w-full divide-y divide-zinc-200 dark:divide-zinc-700';
    
    $wrapperClasses = [
        'overflow-hidden',
        $bordered ? 'shadow ring-1 ring-black ring-opacity-5 rounded-lg' : '',
    ];
    
    $headerClasses = [
        'text-left text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400',
        'bg-zinc-50 dark:bg-zinc-800',
        $compact ? 'px-2 py-1.5' : 'px-3 py-2',
    ];
    
    $footerClasses = [
        'text-left text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400',
        'bg-zinc-50 dark:bg-zinc-800',
        $compact ? 'px-2 py-1.5' : 'px-3 py-2',
    ];
    
    $bodyClasses = 'bg-white divide-y divide-zinc-200 dark:bg-zinc-900 dark:divide-zinc-700';
    
    $rowClasses = [
        $hoverable ? 'hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors' : '',
        $striped ? 'even:bg-zinc-50 dark:even:bg-zinc-800' : '',
    ];
    
    $cellClasses = [
        'whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100',
        $compact ? 'px-2 py-1.5' : 'px-3 py-2',
    ];
    
    $actionCellClasses = [
        'whitespace-nowrap text-sm',
        $compact ? 'px-2 py-1.5' : 'px-3 py-2',
        'text-right',
    ];
    
    // Add Actions header if actions are provided
    $allHeaders = $headers;
    if (is_array($headers) && $actions) {
        $allHeaders[] = 'Actions';
    }
@endphp


<div class="hidden sm:block {{ implode(' ', array_filter($wrapperClasses)) }}">
    <table {{ $attributes->merge(['class' => $baseClasses]) }}>
        @if(!empty($allHeaders) || (is_string($headers) && view()->exists($headers)))
            <thead>
                @if(is_string($headers) && view()->exists($headers))
                    @include($headers, ['actions' => $actions])
                @else
                    <tr>
                        @foreach($allHeaders as $header)
                            <th class="{{ implode(' ', array_filter($headerClasses)) }} {{ $loop->last && $actions ? 'text-right' : '' }}">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                @endif
            </thead>
        @endif
        
        <tbody class="{{ $bodyClasses }}">
            @if(!empty($rows))
                @foreach($rows as $rowIndex => $row)
                    <tr class="{{ implode(' ', array_filter($rowClasses)) }}">
                        @if(is_array($row))
                            @foreach($row as $cell)
                                <td class="{{ implode(' ', array_filter($cellClasses)) }}">
                                    {!! $cell !!}
                                </td>
                            @endforeach
                            @if($actions)
                                <td class="{{ implode(' ', array_filter($actionCellClasses)) }}">
                                    @if(is_callable($actions))
                                        {!! $actions($row, $rowIndex) !!}
                                    @elseif(view()->exists($actions))
                                        @include($actions, ['row' => $row, 'index' => $rowIndex])
                                    @endif
                                </td>
                            @endif
                        @else
                            <td class="{{ implode(' ', array_filter($cellClasses)) }}" colspan="{{ count($allHeaders) }}">
                                {{ $row }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </tbody>
        
        @if($footers)
            <tfoot>
                @if(is_string($footers) && view()->exists($footers))
                    @include($footers, ['actions' => $actions])
                @elseif(is_array($footers))
                    <tr>
                        @foreach($footers as $footer)
                            <th class="{{ implode(' ', array_filter($footerClasses)) }} {{ $loop->last && $actions ? 'text-right' : '' }}">
                                {{ $footer }}
                            </th>
                        @endforeach
                        @if($actions && is_array($footers))
                            <th class="{{ implode(' ', array_filter($footerClasses)) }} text-right"></th>
                        @endif
                    </tr>
                @endif
            </tfoot>
        @endif
    </table>
</div>


@if((is_array($headers) && !empty($headers)) && !empty($rows))
<div class="block sm:hidden space-y-3">
    @foreach($rows as $rowIndex => $row)
        @if(is_array($row))
        <div class="bg-white dark:bg-zinc-900 {{ $bordered ? 'shadow ring-1 ring-black ring-opacity-5 rounded-lg' : '' }} p-3">
            @foreach($row as $index => $cell)
                @if(isset($headers[$index]))
                <div class="flex justify-between items-start py-1.5 {{ !$loop->last || $actions ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider flex-shrink-0 w-1/3">
                        {{ $headers[$index] }}
                    </dt>
                    <dd class="text-sm text-zinc-900 dark:text-zinc-100 text-right flex-1 ml-3">
                        {!! $cell !!}
                    </dd>
                </div>
                @endif
            @endforeach
            @if($actions)
                <div class="flex justify-between items-start py-1.5">
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider flex-shrink-0 w-1/3">
                        Actions
                    </dt>
                    <dd class="text-sm text-right flex-1 ml-3">
                        @if(is_callable($actions))
                            {!! $actions($row, $rowIndex) !!}
                        @elseif(view()->exists($actions))
                            @include($actions, ['row' => $row, 'index' => $rowIndex])
                        @endif
                    </dd>
                </div>
            @endif
        </div>
        @endif
    @endforeach
</div>
@endif