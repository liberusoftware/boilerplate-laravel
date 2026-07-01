<?php

it('merges a config file named after the module at the root namespace', function () {
    // app/Modules/Blog/config/blog.php — module name "Blog" snake-cases to "blog",
    // matching the config file's basename, so ModuleServiceProvider merges it at
    // config('blog.*') instead of the doubled-up config('blog.blog.*').
    expect(config('blog.posts_per_page'))->toBe(10);
    expect(config('blog.allow_comments'))->toBeFalse();
});
