## Disk-based stack and queue implementations for PHP
Tested on PHP 5.3 and PHP 7.0

### Stacks
This library implements a fast and simple disk-based stack that scales elegantly.

Example:
```
$stack = new stack('test.stack');

// push an item onto the stack
$stack->push('Hello');

// and another
$stack->push('World');

// and a few more
for ($i=0;$i<10;$i++)
	$stack->push('test'.$i);

// pop an item off the top
print $stack->pop()."\n";

// loop through the remaining stack items
do {
	$s = $stack->pop();
	print "$s\n";
} while ($s!==false);

```

### Queues
A simple and efficient disk-based queue implementation for PHP

This class provides a simple disk-based queue that can easily support millions or even billions of entries.

The queue file must be periodically "vacummed" to remove stale entries, otherwise the file will grow indefinitely.

Example:
```
$queue = new queue('test.queue');

// add a single entry
$queue->add('hello world');

// add an array of entries
$queue->add(
	array(
		'this',
		'is',
		'a',
		'test'
	)
);

// fetch one item from the head of the queue
print $queue->get();

// fetch multiple items from the queue
print_r($queue->get(4));

// cleanup the queuefile
$queue->vacuum();
```
