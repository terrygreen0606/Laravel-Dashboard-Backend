<div>
    <ul class="covered-vessel-list">
        @for ($i = 0,$iMax = count($vessels); $i < $iMax; $i++)
            <li>{{$i+1}}. {{$vessels[$i]->name}}</li>
        @endfor
    </ul>
</div>