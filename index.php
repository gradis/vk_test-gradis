<?php
function readInput(): array
{
    $input = file('php://stdin', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (count($input) < 3) {
        fwrite(STDERR, "Ошибка: недостаточно входных данных.\n");
        exit;
    }

    $size = explode(' ', $input[0]);
    if (count($size) != 2 || !ctype_digit($size[0]) || !ctype_digit($size[1])) {
        fwrite(STDERR, "Ошибка: неверный формат размера лабиринта.\n");
        exit;
    }
    $rows = (int)$size[0];
    $cols = (int)$size[1];

    $maze = [];
    for ($i = 1; $i <= $rows; $i++) {
        if (!isset($input[$i])) {
            fwrite(STDERR, "Ошибка: структура лабиринта неполная.\n");
            exit;
        }
        $row = explode(' ', $input[$i]);
        if (count($row) != $cols || array_filter($row, function ($cell) {
                return !ctype_digit($cell);
            }) !== []) {
            fwrite(STDERR, "Ошибка: неверные данные в лабиринте.\n");
            exit;
        }
        $maze[] = array_map('intval', $row);
    }

    $coords = explode(' ', $input[$rows + 1]);
    if (count($coords) != 4 || array_filter($coords, function ($coord) {
            return !ctype_digit($coord);
        }) !== []) {
        fwrite(STDERR, "Ошибка: неверный формат координат.\n");
        exit;
    }

    $start = [(int)$coords[0], (int)$coords[1]];
    $end = [(int)$coords[2], (int)$coords[3]];

    if (!isValidCoord($start, $rows, $cols) || !isValidCoord($end, $rows, $cols)) {
        fwrite(STDERR, "Ошибка: координаты выходят за пределы лабиринта.\n");
        exit;
    }

    return [$rows, $cols, $maze, $start, $end];
}

function isValidCoord(array $coord, int $rows, int $cols): bool
{
    return $coord[0] >= 0 && $coord[0] < $rows && $coord[1] >= 0 && $coord[1] < $cols;
}

function findShortestPath(array $maze, array $start, array $end, int $rows, int $cols): array
{
    $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // Теперь [dy, dx].

    $queue = new SplPriorityQueue();

    $costs = array_fill(0, $rows, array_fill(0, $cols, PHP_INT_MAX));

    $prev = [];

    $queue->insert($start, 0);
    $costs[$start[0]][$start[1]] = 0;

    while (!$queue->isEmpty()) {

        $current = $queue->extract();
        [$y, $x] = $current;

        if ($current === $end) {
            return reconstructPath($prev, $start, $end);
        }

        foreach ($directions as [$dy, $dx]) {
            $ny = $y + $dy;
            $nx = $x + $dx;

            if (isValidCoord([$ny, $nx], $rows, $cols) && $maze[$ny][$nx] > 0) {
                $newCost = $costs[$y][$x] + $maze[$ny][$nx];

                if ($newCost < $costs[$ny][$nx]) {
                    $costs[$ny][$nx] = $newCost;
                    $queue->insert([$ny, $nx], -$newCost);
                    $prev["$ny $nx"] = "$y $x";
                }
            }
        }
    }
    fwrite(STDERR, "Ошибка: путь не найден.\n");
    exit;
}

function reconstructPath(array $prev, array $start, array $end): array
{
    $path = [];
    $current = implode(' ', $end);

    while ($current !== implode(' ', $start)) {
        $path[] = explode(' ', $current);
        if (!isset($prev[$current])) {
            fwrite(STDERR, "Ошибка: сбой при восстановлении пути.\n");
            exit;
        }
        $current = $prev[$current];
    }

    $path[] = $start;
    return array_reverse($path);
}

function main(): void
{
    [$rows, $cols, $maze, $start, $end] = readInput();

    if ($maze[$start[0]][$start[1]] === 0 || $maze[$end[0]][$end[1]] === 0) {
        fwrite(STDERR, "Ошибка: стартовая или конечная точка недоступна.\n");
        exit;
    }

    $path = findShortestPath($maze, $start, $end, $rows, $cols);

    foreach ($path as [$x, $y]) {
        echo "$x $y\n";
    }
    echo ".\n";
}
echo '<pre>';
main();