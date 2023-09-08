import {fireEvent, render, screen, waitFor} from '@testing-library/react'
import '@testing-library/jest-dom'
import Console from "./Console";

describe('Console', () => {
    it('handles an input', async () => {
        const { container } = render(<Console context={{}} setters={{}}/>);
        const console = container.querySelector('div');
        console.focus();

        const input = 'Hello, World!';
        input.split('').forEach(key => fireEvent.keyDown(console, {key: key}))

        await waitFor(() => {
            const title = screen.getByText(new RegExp(input));
            expect(title).toBeInTheDocument()
        });
    })
})
