var gulp = require('gulp');
var sass = require('gulp-sass');
var minify = require('gulp-minify');
var watch = require('gulp-watch');
var rename = require('gulp-rename');

gulp.task('sass', function() {
	gulp.src('assets/scss/*.scss')
		.pipe(watch('assets/scss/*.scss'))
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('assets/css'));
});

gulp.task('compress', function() {
	gulp.src(['assets/js/*.js','!assets/js/*.min.js','!assets/js/*-min.js'])
		.pipe(minify({
			ext: '.min.js'
		}))
		.pipe(gulp.dest('assets/js'))
});

gulp.task('default', ['sass','compress'], function() {
	gulp.watch('assets/scss/*.scss',['sass']);
	gulp.watch(['assets/js/*.js','!assets/js/*.min.js','!assets/js/*-min.js'],['compress']);
});