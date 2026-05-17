# Admin performance

The admin panel uses static Velzon assets from `public/vendor/templateweb/master/assets`.
Serve those files with a long immutable cache policy at the web server layer.

Example Nginx rule:

```nginx
location ~* \.(css|js|woff2|woff|ttf|svg|png|jpg|jpeg|gif|ico)$ {
    expires 1y;
    add_header Cache-Control "public, max-age=31536000, immutable";
    access_log off;
}
```

Do not apply this rule to dynamic HTML responses.
