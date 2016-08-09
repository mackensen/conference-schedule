// Require all the things (that we need)
var gulp = require('gulp');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');
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
	gulp.watch(src.js, ['js']);
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

// Minify the JS
gulp.task('js', function() {
    gulp.src(src.js)
        .pipe(uglify({
            mangle: false
        }))
        .pipe(rename({
			suffix: '.min'
		}))
        .pipe(gulp.dest(dest.js))
});

// Let's get this party started
gulp.task('default', ['sass','js'], function() {
	gulp.watch(src.scss, ['sass']);
	gulp.watch(src.js, ['js']);
});