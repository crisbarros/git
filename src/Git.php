<?php
/**
 * Git
 *
 * Copyright (c) 2013-2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2013-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/git
 */

namespace Git;

use DateTime;

/**
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2013-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/git
 */
class Wrapper
{
    /**
     * @var string
     */
    private $repositoryPath;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Singleton $instance The *Singleton* instances of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance($repositoryPath = '')
	{
		static $instance = null;
		if (null === $instance) {
			$class_name = __CLASS__;
			$instance = new $class_name();
		}
		if (!empty($repositoryPath)) {
			$instance->repositoryPath = realpath($repositoryPath);
		}

		return $instance;
	}

    protected function __construct()
    {
    }

    /**
     * @param string $revision
     */
    public function checkout($revision)
    {
        $this->execute(
            'git checkout --force --quiet ' . $revision . ' 2>&1'
        );
    }

    /**
     * @return string
     */
    public function getCurrentBranch()
    {
        $output = $this->execute('git rev-parse --abbrev-ref HEAD');

        return trim($output[0]);
    }

    /**
     * @param  string $from
     * @param  string $to
     * @return string
     */
    public function getDiff($from, $to)
    {
        $output = $this->execute(
            'git diff --no-ext-diff ' . $from . ' ' . $to
        );

        return join("\n", $output);
    }

    /**
     * @return array
     */
    public function getCommitLog($limit = 3)
    {
        $output = $this->execute(
            "git log master --no-merges --format=medium -n {$limit}"
        );

        $numLines  = count($output);
        $revisions = array();
        $author = $sha1 = '';

        for ($i = 0; $i < $numLines; $i++) {
            $tmp = explode(' ', $output[$i]);

            if (count($tmp) == 2 && $tmp[0] == 'commit') {
                $sha1 = $tmp[1];
            } elseif (count($tmp) == 4 && $tmp[0] == 'Author:') {
                $author = join(' ', array_slice($tmp, 1));
            } elseif (count($tmp) == 9 && $tmp[0] == 'Date:') {
                $revisions[] = array(
                  'author'  => $author,
                  'date'    => DateTime::createFromFormat(
                      'D M j H:i:s Y O',
                      join(' ', array_slice($tmp, 3))
                  ),
                  'sha1'    => $sha1,
                  'message' => isset($output[$i+2]) ? trim($output[$i+2]) : ''
                );
            }
        }

        return $revisions;
    }

    /**
     * @param  string  $command
     * @throws RuntimeException
     */
    protected function execute($command)
    {
        $cwd = getcwd();
        chdir($this->repositoryPath);
        exec($command, $output, $returnValue);
        chdir($cwd);

        if ($returnValue !== 0) {
            throw new RuntimeException($output);
        }

        return $output;
    }
}
