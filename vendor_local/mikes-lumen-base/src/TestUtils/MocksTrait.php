<?php

namespace MikesLumenBase\TestUtils;

use Mockery;

trait MocksTrait
{
    /**
     * All of the published events.
     *
     * @var array
     */
    protected $publishedEvents = [];

     /**
     * Mock the publisher so all publish events are silenced.
     *
     * @return $this
     */
    protected function withoutPublisher()
    {
        $mock = Mockery::mock(Publisher::class);
        $mock->shouldReceive('publish')->andReturnUsing(function ($called) {
            $this->publishedEvents[] = $called;
        });
        $this->app->bind('publisher', function () use ($mock) {
            return $mock;
        });
        return $this;
    }

    /**
     * Specify a publish event that is expected to be published.
     *
     * @param  mixed  $publishEvents
     * @param  string  $publishEvent
     * @return $this
     */
    public function expectsPublished($events)
    {
        $events = is_array($events) ? $events : func_get_args();
        $this->withoutPublisher();

        $this->beforeApplicationDestroyed(function () use ($events) {

            $published = $this->getPublishedEvents($events);
            $this->assertEmpty(
                $eventsNotFired = array_diff($events, $published),
                'These expected events were not published: ['.implode(', ', $eventsNotFired).']'
            );
        });
        return $this;
    }


    /**
     * Filter the given events against the fired events.
     *
     * @param  array  $events
     * @return array
     */
    protected function getPublishedEvents(array $events)
    {
        return $this->getDispatched($events, $this->publishedEvents);
    }

     /**
     * Filter the given classes against an array of dispatched classes.
     *
     * @param  array  $classes
     * @param  array  $dispatched
     * @return array
     */
    protected function getDispatched(array $classes, array $dispatched)
    {
        return array_filter($classes, function ($class) use ($dispatched) {
            return $this->wasDispatched($class, $dispatched);
        });
    }
    /**
     * Check if the given class exists in an array of dispatched classes.
     *
     * @param  string  $needle
     * @param  array  $haystack
     * @return bool
     */
    protected function wasDispatched($needle, array $haystack)
    {
        foreach ($haystack as $dispatched) {
            if ((is_string($dispatched) && ($dispatched === $needle || is_subclass_of($dispatched, $needle))) ||
                $dispatched instanceof $needle) {
                return true;
            }
        }
        return false;
    }

    public function mockFetcher()
    {
        $mock = Mockery::mock(Fetcher::class);
        $this->app->bind('fetcher', function () use ($mock) {
            return $mock;
        });
        return $mock;
    }
}
