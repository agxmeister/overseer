import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import Card from "./Card";

describe('Card', () => {
    it('renders a title', () => {
        render(<Card title={"test"} row={'row'} column={'column'}/>)

        const title = screen.getByRole('heading', {
            name: /test/i,
        })

        expect(title).toBeInTheDocument()
    })
})
