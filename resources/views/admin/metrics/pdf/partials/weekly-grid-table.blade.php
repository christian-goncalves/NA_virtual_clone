<table>
    <colgroup>
        <col style="width: {{ (float) data_get($layout, 'col_group_pt', 134.22) }}pt;" />
        <col style="width: {{ (float) data_get($layout, 'col_link_pt', 67.7325) }}pt;" />
        @foreach ($weekdayColumns as $weekdayLabel)
            <col style="width: {{ (float) data_get($layout, 'col_day_pt', 40.68) }}pt;" />
        @endforeach
    </colgroup>
    <thead>
        <tr>
            <th class="col-group">GRUPO</th>
            <th class="col-link">LINK</th>
            @foreach ($weekdayColumns as $weekdayLabel)
                <th class="col-day">{{ $weekdayLabel }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($groups as $groupIndex => $group)
            <tr style="height: {{ (int) data_get($layout, 'row_heights_pt.'.$groupIndex, 26) }}pt;">
                <td class="col-group">
                    <div class="group">{{ $group['group_name'] }}</div>
                    <div class="meta">ID: {{ data_get($group, 'meeting_id', '-') }}</div>
                </td>
                <td class="col-link day-cell">
                    @if (data_get($group, 'link_url') && data_get($group, 'link_url') !== '#')
                        <a
                            href="{{ $group['link_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="vm-btn vm-card-cta-main vm-btn-primary link-btn"
                            title="Entrar na reuniao"
                            data-metrics-event="category_click"
                            data-source-section="pdf"
                            data-meeting-name="{{ data_get($group, 'group_name', '') }}"
                            data-metrics-meeting-signature="{{ data_get($group, 'meeting_id', '-') }}|pdf"
                        >
                            <span class="link-btn-icon" aria-hidden="true">
                                <svg class="link-btn-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" focusable="false" aria-hidden="true">
                                    <path fill="currentColor" d="M512 32c17.7 0 32 14.3 32 32V224c0 17.7-14.3 32-32 32s-32-14.3-32-32V141.3L361.4 259.9c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L434.7 96H352c-17.7 0-32-14.3-32-32s14.3-32 32-32H512zM80 64c-44.2 0-80 35.8-80 80V432c0 44.2 35.8 80 80 80H368c44.2 0 80-35.8 80-80V336c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V144c0-8.8 7.2-16 16-16h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/>
                                </svg>
                            </span>
                            <span class="link-btn-text">Entrar</span>
                        </a>
                    @else
                        <span class="no-slots">-</span>
                    @endif
                </td>
                @foreach ($weekdayColumns as $weekdayKey => $weekdayLabel)
                    @php
                        $entries = data_get($group, 'schedule.'.$weekdayKey, []);
                    @endphp
                    <td class="day-cell">
                        @if (empty($entries))
                            <span class="no-slots">-</span>
                        @else
                            @foreach ($entries as $entry)
                                @php
                                    $formatCode = (string) data_get($entry, 'format', '');
                                    $formatDescription = (string) data_get($entry, 'format_description', '');
                                    if ($formatDescription === '') {
                                        $formatDescription = match ($formatCode) {
                                            'F' => 'Fechada',
                                            'E' => 'Estudo',
                                            'A' => 'Aberta',
                                            default => 'Formato da reuniao',
                                        };
                                    }
                                @endphp
                                <div class="slot">
                                    <span class="slot-time">{{ data_get($entry, 'start') }}</span>
                                    <span class="slot-marker"><span class="pdf-badge {{ data_get($entry, 'format_badge_class') }}" title="{{ $formatDescription }}" aria-label="{{ $formatDescription }}"></span></span>
                                </div>
                            @endforeach
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
