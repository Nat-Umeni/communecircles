<template>
    <div class="px-2 py-1">
        <!-- NOTE: the room around the table keeps a row grown on hover (TableRow) inside the scrolling list around it, so
        no scrollbar flickers on; the header stays in view as the list scrolls, above a grown row. -->
        <table class="w-full text-left">
            <thead class="sticky top-0 z-10 bg-surface text-sm text-muted-foreground">
                <tr class="border-b border-border">
                    <!-- The column headers: TableHead. -->
                    <slot name="head" />
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <!-- The rows: TableRow, each with TableCell. -->
                <slot />
            </tbody>
        </table>
    </div>
</template>
