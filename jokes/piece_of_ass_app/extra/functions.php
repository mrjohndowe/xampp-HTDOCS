<?php

function hairColor(): array
{
  $hColor = [
    'BLACK' => 'BLACK',
    'BROWN' => 'BROWN',
    'BLONDE' => 'BLONDE',
    'RED' => 'RED',
    'GRAY' => 'GRAY',
    'BALD' => 'BALD',
    'OTHER' => 'OTHER'
  ];

  return $hColor;
}

function hairColorSelect(): string
{
  $hColor = hairColor();
  $hairSelection = '<select name="hair_color">';
  $hairSelection .= '<option value="">Select Hair Color</option>';
  foreach ($hColor as $key => $value) {
    $hairSelection .= '<option value="' . $key . '">' . $value . '</option>';
  }
  $hairSelection .= '</select>';
  return $hairSelection;
}

function eyeColor(): array
{
  $eColor = [
    'BROWN' => 'BROWN',
    'BLUE' => 'BLUE',
    'GREEN' => 'GREEN',
    'HAZEL' => 'HAZEL',
    'GRAY' => 'GRAY',
    'OTHER' => 'OTHER'
  ];
  return $eColor;
}

function eyeColorSelect(): string
{
  $eColor = eyeColor();
  $eyeSelection = '<select name="eye_color">';
  $eyeSelection .= '<option value="">Select Eye Color</option>';
  foreach ($eColor as $key => $value) {
    $eyeSelection .= '<option value="' . $key . '">' . $value . '</option>';
  }
  $eyeSelection .= '</select>';
  return $eyeSelection;
}

function box(string $name, string $label = ''): string
{
  return '<label class="check"><input type="checkbox" name="' . $name . '"> <span>' . $label . '</span></label>';
}


?>
