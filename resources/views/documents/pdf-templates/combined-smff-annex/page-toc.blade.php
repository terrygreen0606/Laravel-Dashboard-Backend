<div class="page">
    <div class="toc">
        <h1 class="page-header">Table of Contents</h1>
        <table class="toc-table">
            @foreach($items as $item)
                <tr {{ $item['subtopic'] ? 'class="sub-topic"' : ''}}>
                    <td>{{$item['title']}}</td>
                    <td>{{$item['index']}}</td>
                </tr>
            @endforeach
        </table>
        <p style="font-weight:bold;font-size:18px;">Documents <span style="font-weight:normal;">(Attached)</span></p>
        @foreach($addendums as $item)
            <p style="font-size:14px; padding-left: 20px;">{{$item['title']}}</p>
        @endforeach
    </div>
</div>