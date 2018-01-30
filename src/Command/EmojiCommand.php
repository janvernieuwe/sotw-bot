<?php


namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmojiCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:list')
            ->setDescription('List server emoji')
            ->setHelp('Lists all emoji from the server');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $server = $this->getContainer()->get('discord.server');
        $channel = $this->getContainer()->get('discord.channel.emoji');
        $messages = $channel->getMessages(100);
        foreach ($messages as $message) {
            if (count($message['attachments'])) {
                $file = $message['attachments'][0];
                $encodedData = base64_encode(file_get_contents($file['url']));
                $info = pathinfo($file['url']);
                $image = sprintf('data:image/%s;base64,%s', $info['extension'], $encodedData);
                if (!$server->hasEmoji($info['filename'])) {
                    $emoji = $server->addEmoji($info['filename'], $image);
                    $channel->message("<:{$emoji->name}:{$emoji->id}>");
                    sleep(1);
                }
            }
        }
    }
}
