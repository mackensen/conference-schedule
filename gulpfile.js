// Require all the things (that we need)
var gulp = require('gulp');
var sass = require('gulp-sass');
var minify = require('gulp-minify');
var watch = require('gulp-watch');
var autoprefixer = require('gulp-autoprefixer');
var rename = require('gulp-rename');

// Define the source paths for each file type
var src = {
    scss: './assets/scss/*.scss',
    js: ['assets/js/*.js','!assets/js/*.min.js','!assets/js/*-min.js']
};

// Define the destination paths for each file type
var dest = {
	scss: './assets/css',
	js: './assets/js'
}

// I've got my eyes on you(r file changes)
gulp.task('watch', function() {
	gulp.watch(src.scss, ['sass']);
	gulp.watch(src.js, ['compress']);
});

// Sass is pretty awesome, right?
gulp.task('sass', function() {
	return gulp.src(src.scss)
		.pipe(sass({
			outputStyle: 'compressed'
		})
		.on('error', sass.logError))
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest(dest.scss));
});

// Compress all the JS
gulp.task('compress', function() {
	return gulp.src(src.js)
		.pipe(minify({
			ext: '.min.js',
			mangle: false
		}))
		.pipe(gulp.dest(dest.js))
});

// Let's get this party started
gulp.task('default', ['sass','compress'], function() {
	gulp.watch(src.scss, ['sass']);
	gulp.watch(src.js, ['compress']);
});