DrupalCI - Test Runner
======================

Running the test runner tests
-----------------------------

There is currently no continuous integration set up for drupalci_testbot. Run tests locally, please.

At this point, the tests for drupalci are somewhat coupled to the environment, so we must start clean:

- Use a native environment, either on a testbot machine or using the virtual machine provided by vagrant or similar.
- Clear out all config. This is easiest to accomplish by removing `~/.drupalci`.
- Run the tests using `./bin/phpunit`.
- Depending on your needs, you can exclude some tests with hard dependencies with `--exclude-group`. Notably `@group docker`.

        $ git clone --branch dev https://git.drupal.org/project/drupalci_testbot.git
        $ cd drupalci_testbot
        $ vagrant up
        // Wait a while...
        $ vagrant ssh
        $ sudo docker -d &
        $ rm -rf ~/.drupalci
        $ ./drupalci init
        // Pull down the images.
        // You'll need the stable versions of PHP 5.3, 5.5, and 7.0.
        // These are in the web-*.* images.
        // Re-run drupalci init to pick more images.
        $ cd /tmp
        $ git clone https://git.drupal.org/project/drupal.git
        // Install dependencies for Drupal
        $ cd /tmp/drupal
        $ ~/drupalci_testbot/composer.phar install
        $ cd ~/drupalci_testbot
        // Remove existing containers.
        $ ./drupalci docker-rm containers
        // Run the tests.
        $ ./bin/phpunit
        // Tests run.
        $ ./bin/phpunit --exclude-group docker
        // Tests run without docker dependencies.
        $
