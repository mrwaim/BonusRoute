00|MYNAYHA|1||||||||||||||||||||||||||
<?php $index = 1; ?>
@foreach($data_excel as $row)
01|{{$row[0]}}|Domestic Payments (MY)||{{$row[1]}}|||{{sprintf('%05d', $index)}}||{{$row[12]}}|MYR|{{$row[3]}}.00|Y|MYR|562188319014|{{$row[4]}}|||Y|{{$row[5]}}|NOT APPLICABLE|NOT APPLICABLE|||||{{$row[8]}}||||||||||{{$row[9]}}||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||{{$row[11]}}|||||||01|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
02|PA|3|{{$row[10]}}|||{{$row[11]}}|||||||{{$row[3]}}.00||||||
<?php $index++; ?>
@endforeach
99|{{$index}}|{{$total}}.00|95280||||||||||||||||||||||||||