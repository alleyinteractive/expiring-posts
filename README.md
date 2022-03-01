# Expiring Posts

Automatically expire posts after a certain period of time. Checks the post's
published and modified date to see if the post is expired and will perform an
action on the post (draft/trash/delete it).

## Usage

### Registering Post Types

Posts that are expired can be made into `draft` or `trash` posts or outright
deleted.

#### Register a post type to be drafted after a month

```php
expiring_posts_add_post_type(
	$post_type,
	[
		'action'       => 'draft',
		'expire_after' => MONTH_IN_SECONDS,
	],
);
```

#### Register a post type to be trashed after a week

```php
expiring_posts_add_post_type(
	$post_type,
	[
		'action'       => 'trash',
		'expire_after' => WEEK_IN_SECONDS,
	],
);
```

#### Register a post type to be deleted after a week

```php
expiring_posts_add_post_type(
	$post_type,
	[
		'action'       => 'delete',
		'expire_after' => WEEK_IN_SECONDS,
	],
);
```

By default, the post type will be set to be drafted after a year.

### Hooks

#### `expiring_posts_is_post_expired`

Filter applied to check if a post is expired.

Props:

- `$is_expired`: `bool`  Whether the post is expired.
- `$post`: `WP_Post` Post to check.
- `$threshold`: `int`  Threshold to check against (unix timestamp).

#### `expiring_posts_cron_interval`

Interval to run the expiration check. Defaults to every hour.

#### `expiring_posts_expired`

Action fired when a post was expired.

Props:

- `$post_id`: `int` Post ID
- `$post`: `WP_Post` Post object.

### Unregister a post type

```php
expiring_posts_remove_post_type( $post_type );
```

## Testing

```bash
composer test
```

## License

Released under the [GPL
v2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) license.
