<?php

# not sure what should happen with these

function sassy_compass__stylesheet_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__font_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__image_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__url($path, $only_path = FALSE) {
	if (!$path = sassy_compass__resolve_path($path)) {
		return new SassBoolean(FALSE);
	}

	if ($only_path) {
		return new SassString($path);
	}
	return new SassString("url('$path')");
}