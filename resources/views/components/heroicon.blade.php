@props(['name', 'class' => 'size-5'])

@php
    // Map of all available Heroicons in Flux UI
    $iconMap = [
        'academic-cap' => 'academic-cap',
        'adjustments-horizontal' => 'adjustments-horizontal',
        'adjustments-vertical' => 'adjustments-vertical',
        'archive-box' => 'archive-box',
        'arrow-down' => 'arrow-down',
        'arrow-down-tray' => 'arrow-down-tray',
        'arrow-left' => 'arrow-left',
        'arrow-path' => 'arrow-path',
        'arrow-right' => 'arrow-right',
        'arrow-up' => 'arrow-up',
        'arrow-up-tray' => 'arrow-up-tray',
        'arrow-trending-down' => 'arrow-trending-down',
        'arrow-trending-up' => 'arrow-trending-up',
        'at-symbol' => 'at-symbol',
        'banknotes' => 'banknotes',
        'bars-3' => 'bars-3',
        'beaker' => 'beaker',
        'bell' => 'bell',
        'bell-alert' => 'bell-alert',
        'bolt' => 'bolt',
        'book-open' => 'book-open',
        'bookmark' => 'bookmark',
        'briefcase' => 'briefcase',
        'bug-ant' => 'bug-ant',
        'building-library' => 'building-library',
        'building-office' => 'building-office',
        'building-office-2' => 'building-office-2',
        'building-storefront' => 'building-storefront',
        'cake' => 'cake',
        'calculator' => 'calculator',
        'calendar' => 'calendar',
        'calendar-days' => 'calendar-days',
        'camera' => 'camera',
        'chart-bar' => 'chart-bar',
        'chart-pie' => 'chart-pie',
        'chat-bubble-left' => 'chat-bubble-left',
        'chat-bubble-oval-left' => 'chat-bubble-oval-left',
        'check' => 'check',
        'check-badge' => 'check-badge',
        'check-circle' => 'check-circle',
        'chevron-down' => 'chevron-down',
        'chevron-right' => 'chevron-right',
        'clipboard' => 'clipboard',
        'clipboard-document' => 'clipboard-document',
        'clipboard-document-check' => 'clipboard-document-check',
        'clock' => 'clock',
        'cloud' => 'cloud',
        'code-bracket' => 'code-bracket',
        'cog' => 'cog',
        'cog-6-tooth' => 'cog-6-tooth',
        'cog-8-tooth' => 'cog-8-tooth',
        'command-line' => 'command-line',
        'computer-desktop' => 'computer-desktop',
        'cpu-chip' => 'cpu-chip',
        'credit-card' => 'credit-card',
        'cube' => 'cube',
        'currency-dollar' => 'currency-dollar',
        'cursor-arrow-rays' => 'cursor-arrow-rays',
        'device-phone-mobile' => 'device-phone-mobile',
        'document' => 'document',
        'document-plus' => 'document-plus',
        'document-text' => 'document-text',
        'envelope' => 'envelope',
        'envelope-open' => 'envelope-open',
        'exclamation-circle' => 'exclamation-circle',
        'exclamation-triangle' => 'exclamation-triangle',
        'eye' => 'eye',
        'face-smile' => 'face-smile',
        'film' => 'film',
        'fire' => 'fire',
        'flag' => 'flag',
        'folder' => 'folder',
        'folder-open' => 'folder-open',
        'funnel' => 'funnel',
        'gift' => 'gift',
        'globe-alt' => 'globe-alt',
        'hand-raised' => 'hand-raised',
        'hand-thumb-up' => 'hand-thumb-up',
        'heart' => 'heart',
        'home' => 'home',
        'identification' => 'identification',
        'inbox' => 'inbox',
        'information-circle' => 'information-circle',
        'key' => 'key',
        'light-bulb' => 'light-bulb',
        'link' => 'link',
        'list-bullet' => 'list-bullet',
        'lock-closed' => 'lock-closed',
        'lock-open' => 'lock-open',
        'magnifying-glass' => 'magnifying-glass',
        'map' => 'map',
        'megaphone' => 'megaphone',
        'microphone' => 'microphone',
        'moon' => 'moon',
        'newspaper' => 'newspaper',
        'paper-airplane' => 'paper-airplane',
        'paper-clip' => 'paper-clip',
        'pencil' => 'pencil',
        'pencil-square' => 'pencil-square',
        'phone' => 'phone',
        'photo' => 'photo',
        'play' => 'play',
        'plus' => 'plus',
        'plus-circle' => 'plus-circle',
        'power' => 'power',
        'presentation-chart-bar' => 'presentation-chart-bar',
        'printer' => 'printer',
        'puzzle-piece' => 'puzzle-piece',
        'qr-code' => 'qr-code',
        'question-mark-circle' => 'question-mark-circle',
        'radio' => 'radio',
        'receipt-percent' => 'receipt-percent',
        'rocket-launch' => 'rocket-launch',
        'scale' => 'scale',
        'server' => 'server',
        'server-stack' => 'server-stack',
        'share' => 'share',
        'shield-check' => 'shield-check',
        'shopping-bag' => 'shopping-bag',
        'shopping-cart' => 'shopping-cart',
        'signal' => 'signal',
        'sparkles' => 'sparkles',
        'speaker-wave' => 'speaker-wave',
        'square-3-stack-3d' => 'square-3-stack-3d',
        'star' => 'star',
        'sun' => 'sun',
        'table-cells' => 'table-cells',
        'tag' => 'tag',
        'ticket' => 'ticket',
        'trash' => 'trash',
        'trophy' => 'trophy',
        'truck' => 'truck',
        'user' => 'user',
        'user-circle' => 'user-circle',
        'user-group' => 'user-group',
        'user-plus' => 'user-plus',
        'users' => 'users',
        'video-camera' => 'video-camera',
        'wallet' => 'wallet',
        'wifi' => 'wifi',
        'wrench' => 'wrench',
        'wrench-screwdriver' => 'wrench-screwdriver',
        'x-circle' => 'x-circle',
        'x-mark' => 'x-mark',
    ];
    
    $iconName = $iconMap[$name] ?? 'bolt';
@endphp

@switch($iconName)
    @case('academic-cap')
        <flux:icon.academic-cap {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('archive-box')
        <flux:icon.archive-box {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-down')
        <flux:icon.arrow-down {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-down-tray')
        <flux:icon.arrow-down-tray {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-left')
        <flux:icon.arrow-left {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-path')
        <flux:icon.arrow-path {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-right')
        <flux:icon.arrow-right {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-up')
        <flux:icon.arrow-up {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('arrow-up-tray')
        <flux:icon.arrow-up-tray {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('at-symbol')
        <flux:icon.at-symbol {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('bars-3')
        <flux:icon.bars-3 {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('bell')
        <flux:icon.bell {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('bolt')
        <flux:icon.bolt {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('book-open')
        <flux:icon.book-open {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('bookmark')
        <flux:icon.bookmark {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('briefcase')
        <flux:icon.briefcase {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('building-office')
        <flux:icon.building-office {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('calculator')
        <flux:icon.calculator {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('calendar')
        <flux:icon.calendar {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('calendar-days')
        <flux:icon.calendar-days {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('camera')
        <flux:icon.camera {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('chart-bar')
        <flux:icon.chart-bar {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('chart-pie')
        <flux:icon.chart-pie {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('check')
        <flux:icon.check {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('check-circle')
        <flux:icon.check-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('chevron-down')
        <flux:icon.chevron-down {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('chevron-right')
        <flux:icon.chevron-right {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('clipboard')
        <flux:icon.clipboard {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('clock')
        <flux:icon.clock {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('cloud')
        <flux:icon.cloud {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('code-bracket')
        <flux:icon.code-bracket {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('cog')
        <flux:icon.cog {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('cog-6-tooth')
        <flux:icon.cog-6-tooth {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('command-line')
        <flux:icon.command-line {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('computer-desktop')
        <flux:icon.computer-desktop {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('credit-card')
        <flux:icon.credit-card {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('cube')
        <flux:icon.cube {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('currency-dollar')
        <flux:icon.currency-dollar {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('document')
        <flux:icon.document {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('document-plus')
        <flux:icon.document-plus {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('document-text')
        <flux:icon.document-text {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('envelope')
        <flux:icon.envelope {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('exclamation-circle')
        <flux:icon.exclamation-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('exclamation-triangle')
        <flux:icon.exclamation-triangle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('eye')
        <flux:icon.eye {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('fire')
        <flux:icon.fire {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('flag')
        <flux:icon.flag {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('folder')
        <flux:icon.folder {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('folder-open')
        <flux:icon.folder-open {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('funnel')
        <flux:icon.funnel {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('gift')
        <flux:icon.gift {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('globe-alt')
        <flux:icon.globe-alt {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('heart')
        <flux:icon.heart {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('home')
        <flux:icon.home {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('identification')
        <flux:icon.identification {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('inbox')
        <flux:icon.inbox {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('information-circle')
        <flux:icon.information-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('key')
        <flux:icon.key {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('light-bulb')
        <flux:icon.light-bulb {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('link')
        <flux:icon.link {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('list-bullet')
        <flux:icon.list-bullet {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('lock-closed')
        <flux:icon.lock-closed {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('lock-open')
        <flux:icon.lock-open {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('magnifying-glass')
        <flux:icon.magnifying-glass {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('map')
        <flux:icon.map {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('megaphone')
        <flux:icon.megaphone {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('microphone')
        <flux:icon.microphone {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('moon')
        <flux:icon.moon {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('newspaper')
        <flux:icon.newspaper {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('paper-airplane')
        <flux:icon.paper-airplane {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('paper-clip')
        <flux:icon.paper-clip {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('pencil')
        <flux:icon.pencil {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('pencil-square')
        <flux:icon.pencil-square {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('phone')
        <flux:icon.phone {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('photo')
        <flux:icon.photo {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('play')
        <flux:icon.play {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('plus')
        <flux:icon.plus {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('plus-circle')
        <flux:icon.plus-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('power')
        <flux:icon.power {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('presentation-chart-bar')
        <flux:icon.presentation-chart-bar {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('printer')
        <flux:icon.printer {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('puzzle-piece')
        <flux:icon.puzzle-piece {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('qr-code')
        <flux:icon.qr-code {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('question-mark-circle')
        <flux:icon.question-mark-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('rocket-launch')
        <flux:icon.rocket-launch {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('scale')
        <flux:icon.scale {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('server')
        <flux:icon.server {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('share')
        <flux:icon.share {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('shield-check')
        <flux:icon.shield-check {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('shopping-bag')
        <flux:icon.shopping-bag {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('shopping-cart')
        <flux:icon.shopping-cart {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('signal')
        <flux:icon.signal {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('sparkles')
        <flux:icon.sparkles {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('star')
        <flux:icon.star {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('sun')
        <flux:icon.sun {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('tag')
        <flux:icon.tag {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('ticket')
        <flux:icon.ticket {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('trash')
        <flux:icon.trash {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('trophy')
        <flux:icon.trophy {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('truck')
        <flux:icon.truck {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('user')
        <flux:icon.user {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('user-circle')
        <flux:icon.user-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('user-group')
        <flux:icon.user-group {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('user-plus')
        <flux:icon.user-plus {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('users')
        <flux:icon.users {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('video-camera')
        <flux:icon.video-camera {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('wallet')
        <flux:icon.wallet {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('wifi')
        <flux:icon.wifi {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('wrench')
        <flux:icon.wrench {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('wrench-screwdriver')
        <flux:icon.wrench-screwdriver {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('x-circle')
        <flux:icon.x-circle {{ $attributes->merge(['class' => $class]) }} />
        @break
    @case('x-mark')
        <flux:icon.x-mark {{ $attributes->merge(['class' => $class]) }} />
        @break
    @default
        <flux:icon.bolt {{ $attributes->merge(['class' => $class]) }} />
@endswitch