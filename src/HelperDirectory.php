<?php declare(strict_types=1);

namespace HaaseIT\VAHI;


class HelperDirectory
{
    /** @var string */
    protected $requestUri;

    /** @var string */
    protected $currentPath;

    public function __construct(string $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        $this->currentPath = realpath(PATH_PUBLIC.$this->requestUri);

        if ($this->currentPath === false || substr($this->currentPath, 0, strlen(PATH_PUBLIC)) != PATH_PUBLIC) {
            throw new \Exception('Directory not available.');
        }
    }

    public function getCurrentDirectory(): array
    {
        $entries = scandir($this->currentPath);
        $entries = $this->cleanupDirectoryEntries($entries);
        $entries = $this->sortDirectoryEntries($entries);

        return $entries;
    }

    protected function sortDirectoryEntries(array $nodes): array
    {
        natsort($nodes);

        $return = [];
        foreach ($nodes as $node) {
            $return[$this->getNodeType($node)][] = $node;
        }

        return $return;
    }

    protected function getNodeType(string $node): string
    {
        $return = 'undefined';
        if (is_dir($this->currentPath.DIRECTORY_SEPARATOR.$node)) {
            $return = 'directories';
        }

        if (is_file($this->currentPath.DIRECTORY_SEPARATOR.$node)) {
            if (getimagesize($this->currentPath.DIRECTORY_SEPARATOR.$node)) {
                $return = 'images';
            } else {
                $return = 'files';
            }
        }

        return $return;
    }

    protected function cleanupDirectoryEntries(array $nodes): array
    {
        $cleanNodes = [];
        foreach ($nodes as $node) {
            if ($this->checkForValidNodeName($node) && !$this->checkForHiddenNodeName($node)) {
                $cleanNodes[] = $node;
            }
        }

        return $cleanNodes;
    }

    protected function checkForValidNodeName(string $node): bool
    {
        $blacklist = ['.'];
        if ($this->requestUri == '/') {
            $blacklist[] = '..';
        }

        return !in_array($node, $blacklist);
    }

    protected function checkForHiddenNodeName(string $node): bool
    {
        return $node != '..' && substr($node, 0, 1) == '.';
    }
}
