<tr class="d_block" id="{{ $row->id }}">

    {!! $row->columns !!}

    <td class="text-right">
        {!! $row->hidden !!}
        
        @if ($row->isOrderable)
            <a class="btn btn-default handle btn-xs" style="display:none"><i class="fa fa-arrows"></i></a>&nbsp;
        @endif
        <a class="btn btn-default btn-xs text-danger d_remove" style="display:none"><i class="fa fa-times"></i></a>
    </td>
</tr>
