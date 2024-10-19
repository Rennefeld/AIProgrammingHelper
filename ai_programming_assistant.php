<?php

class AIProgrammingAssistant {
    private $scriptName;
    private $conversationFile = 'ai_conversation.txt';
    private $codebaseFile = 'codebase_snapshot.txt';

    public function __construct() {
        $this->scriptName = basename(__FILE__);
    }

    public function run() {
        while (true) {
            $this->displayMenu();
            $choice = trim(fgets(STDIN));
            switch ($choice) {
                case '1':
                    $this->snapshotCodebase();
                    break;
                case '2':
                    $this->viewCodebaseSnapshot();
                    break;
                case '3':
                    $this->addToConversation('human');
                    break;
                case '4':
                    $this->addToConversation('ai');
                    break;
                case '5':
                    $this->viewConversation();
                    break;
                case '6':
                    $this->exportForAI();
                    break;
                case '7':
                    echo "Exiting the program. Goodbye!\n";
                    exit;
                default:
                    echo "Invalid choice. Please try again.\n";
            }
        }
    }

    private function displayMenu() {
        echo "\nAI Programming Assistant\n";
        echo "1. Take a snapshot of the codebase\n";
        echo "2. View codebase snapshot\n";
        echo "3. Add human message to conversation\n";
        echo "4. Add AI response to conversation\n";
        echo "5. View conversation\n";
        echo "6. Export codebase and conversation for AI\n";
        echo "7. Exit\n";
        echo "Enter your choice: ";
    }

    private function snapshotCodebase() {
        ob_start();
        $this->readFiles('.', '');
        $content = ob_get_clean();
        file_put_contents($this->codebaseFile, $content);
        echo "Codebase snapshot saved to {$this->codebaseFile}\n";
    }

    private function readFiles($dir, $baseDir) {
        $files = scandir($dir);
        $readmeContent = '';
        $otherContent = '';
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $relativePath = $baseDir ? ($baseDir . DIRECTORY_SEPARATOR . $file) : $file;
            
            if ($relativePath === $this->scriptName || $relativePath === $this->conversationFile || $relativePath === $this->codebaseFile) continue;
            
            if (is_dir($path)) {
                list($subReadme, $subOther) = $this->readFiles($path, $relativePath);
                $readmeContent .= $subReadme;
                $otherContent .= $subOther;
            } else {
                $content = "File: $relativePath\nContent:\n" . file_get_contents($path) . "\n" . str_repeat('-', 80) . "\n\n";
                if (preg_match('/^readme(\.md|\.txt)?$/i', $file)) {
                    $readmeContent .= $content;
                } else {
                    $otherContent .= $content;
                }
            }
        }
        return [$readmeContent, $otherContent];
    }

    private function viewCodebaseSnapshot() {
        if (file_exists($this->codebaseFile)) {
            echo file_get_contents($this->codebaseFile);
        } else {
            echo "No codebase snapshot available. Take a snapshot first.\n";
        }
    }

    private function addToConversation($type) {
        echo ($type === 'human' ? "Enter your message: " : "Enter AI's response: ");
        $message = '';
        while ($line = fgets(STDIN)) {
            if (trim($line) === 'EOF') break;
            $message .= $line;
        }
        $formattedMessage = date('Y-m-d H:i:s') . " - " . ucfirst($type) . ":\n" . $message . "\n";
        file_put_contents($this->conversationFile, $formattedMessage, FILE_APPEND);
        echo "Message added to the conversation.\n";
    }

    private function viewConversation() {
        if (file_exists($this->conversationFile)) {
            echo file_get_contents($this->conversationFile);
        } else {
            echo "No conversation recorded yet.\n";
        }
    }

    private function exportForAI() {
        $export = "CONVERSATION AND CONTEXT:\n\n";
        $export .= file_exists($this->conversationFile) ? file_get_contents($this->conversationFile) : "No conversation recorded yet.\n";
        $export .= "\nCODEBASE SNAPSHOT:\n\n";
        
        if (file_exists($this->codebaseFile)) {
            $codebaseContent = file_get_contents($this->codebaseFile);
            $readmeSection = '';
            $otherSection = '';
            
            if (preg_match('/README FILES:(.*?)OTHER FILES:/s', $codebaseContent, $matches)) {
                $readmeSection = trim($matches[1]);
                $otherSection = substr($codebaseContent, strpos($codebaseContent, 'OTHER FILES:'));
            } else {
                $otherSection = $codebaseContent;
            }
            
            if ($readmeSection) {
                $export .= "README FILES:\n\n$readmeSection\n\n";
            } else {
                $export .= "No README files found in the project.\n\n";
            }
            
            $export .= $otherSection;
        } else {
            $export .= "No codebase snapshot available.\n";
        }
        
        $filename = 'ai_export_' . date('YmdHis') . '.txt';
        file_put_contents($filename, $export);
        echo "Exported conversation and codebase to $filename\n";
    }
}

$assistant = new AIProgrammingAssistant();
$assistant->run();
